<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\StravaClient;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $stravaClient = new StravaClient();
        $stravaClient->auth();
//        $stravaClient->auth();
    }

    public function callback(Request $request)
    {
        $stravaClient = new StravaClient();
        $code = $request->input('code');
        $state = $request->input('state');

        $stravaClient->getToken($code);
        return $code;
    }


    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function showInfo()
    {
        $stravaClient = new StravaClient();
        $stravaClient->showInfo();
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
        $aspect_type = $request['aspect_type']; // "create" | "update" | "delete"
        $event_time = $request['event_time']; // time the event occurred
        $object_id = $request['object_id']; // activity ID | athlete ID
        $object_type = $request['object_type']; // "activity" | "athlete"
        $owner_id = $request['owner_id']; // athlete ID
        $subscription_id = $request['subscription_id']; // push subscription ID receiving the event
        $updates = $request['updates']; // activity update: {"title" | "type" | "private": true/false} ; app deauthorization: {"authorized": false}

        $stravaClient = new StravaClient();
        $stravaClient->showInfo();

        Log::channel('strava')->info(json_encode($request->all()));

        return response('EVENT_RECEIVED', Response::HTTP_OK);
    }

}
