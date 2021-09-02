<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\StravaClient;

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

    public function validateCallback(Request $request)
    {
        // Your verify token. Should be a random string.
        $VERIFY_TOKEN = "STRAVA";
        // Parses the query params
//        $mode = $request->input('hub_mode');
//        $token = $request->input('hub_verify_token');
        $challenge = $request->query('hub_challenge');
//        echo '{"hub.challenge":"'.$challenge.'"}';
        return response()->json([
            'hub.challenge' => $challenge,
        ]);
//         Checks if a token and mode is in the query string of the request
//        if ($mode && $token) {
//            // Verifies that the mode and token sent are valid
//            if ($mode === 'subscribe' && $token === $VERIFY_TOKEN) {
//                // Responds with the challenge token from the request
//                return response()->json([
//                    'hub.challenge' => $challenge,
//                ]);
//            } else {
//                // Responds with '403 Forbidden' if verify tokens do not match
//                return "Fail";
//            }
//        }
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
