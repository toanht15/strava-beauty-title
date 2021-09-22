<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Subscriber;
use App\Util;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Http\StravaClient;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Provider\AbstractProvider;
use Strava\API\Exception;
use Strava\API\OAuth;

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $util = new Util();
            $util->makeAuthLink();
        } catch (Exception $e) {
            print $e->getMessage();
            Log::error($e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        try {
            $util = new Util();
            $code = $request->input('code');
            $state = $request->input('state');

            $tokens = $util->getToken($code);
            Log::channel("slack")->info("Strava auth code: " . $code);

            $subscriber = Subscriber::find($tokens['athlete']['id']);
            if (!$subscriber) {
                $subscriber = new Subscriber();
            }

            $subscriber->id = $tokens['athlete']['id'];
            $subscriber->access_token = $tokens['access_token'];
            $subscriber->refresh_token = $tokens['refresh_token'];
            $subscriber->expires_at = $tokens['expires_at'];
            $subscriber->save();

            return "OK";
        } catch (Exception $e) {
            print $e->getMessage();
            Log::error($e->getMessage());
        }
    }


    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function showInfo()
    {
        try {
            $stravaClient = new StravaClient(91678383, 5899075283);
//            $stravaClient = new StravaClient(39375936, 5908014844);
            $stravaClient->saveActivity();
//            $activity = $stravaClient->checkSubscribe();

            $stats = $stravaClient->createStats();
            dd($stats);
        } catch (Exception $e) {
            print $e->getMessage();
            Log::error($e->getMessage());
        }
    }

    public function subscribe()
    {
        $stravaClient = new StravaClient();
        $stravaClient->subscribeWebhook();
    }

    public function check()
    {
        $stravaClient = new StravaClient();
        $stravaClient->checkSubscribe();
    }

    public function refresh()
    {
        $stravaClient = new StravaClient();
        $stravaClient->refreshToken();
    }

    public function validateCallback(Request $request)
    {
        // Your verify token. Should be a random string.
        $VERIFY_TOKEN = "STRAVA";
        // Parses the query params
        $mode = $request->input('hub_mode');
        $token = $request->input('hub_verify_token');
        $challenge = $request->query('hub_challenge');
//         Checks if a token and mode is in the query string of the request
        if ($mode && $token) {
            // Verifies that the mode and token sent are valid
            if ($mode === 'subscribe' && $token === $VERIFY_TOKEN) {
                // Responds with the challenge token from the request
                return response()->json([
                    'hub.challenge' => $challenge,
                ]);
            } else {
                // Responds with '403 Forbidden' if verify tokens do not match
                return "Fail";
            }
        }
    }

    public function updateActivity(Request $request) {
        try {
            Log::info("Start update activity");
            $aspect_type = $request['aspect_type']; // "create" | "update" | "delete"
            $event_time = $request['event_time']; // time the event occurred
            $object_id = $request['object_id']; // activity ID | athlete ID
            $object_type = $request['object_type']; // "activity" | "athlete"
            $owner_id = $request['owner_id']; // athlete ID
            $subscription_id = $request['subscription_id']; // push subscription ID receiving the event
            $updates = $request['updates']; // activity update: {"title" | "type" | "private": true/false} ; app deauthorization: {"authorized": false}

            if ($aspect_type == "create" && $object_type == "activity") {
                $stravaClient = new StravaClient($owner_id, $object_id);
                $stravaClient->saveActivity();
                $stravaClient->updateActivity();
            }

            Log::info(json_encode($request->all()));
            return response('EVENT_RECEIVED', Response::HTTP_OK);
        } catch (Exception $e) {
            print $e->getMessage();
            Log::error($e->getMessage());
        }
    }
}
