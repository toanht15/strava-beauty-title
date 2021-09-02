<?php

namespace App\Http;

use League\OAuth2\Client\Provider\AbstractProvider;
use Strava\API\OAuth;
use Strava\API\Exception;

use Strava\API\Client;
use Strava\API\Service\REST;


class StravaClient
{
    public function auth()
    {
        try {
            $options = [
                'clientId' => 69573,
                'clientSecret' => '3bd7e053018564c39cc8da5f846a0d91954eded8',
                'redirectUri' => 'http://127.0.0.1:8000/callback'
            ];
            $oauth = new OAuth($options);
            print '<a href="' . $oauth->getAuthorizationUrl([
                    // Uncomment required scopes.
                    'scope' => [
                        'read',
                        'read_all',
                        'activity:read',
                        'activity:write'
                        // 'write',
                        // 'view_private',
                    ]
                ]) . '">Connect</a>';
        } catch (Exception $e) {
            print $e->getMessage();
        }
    }

    public function getToken($code)
    {
        try {
            $options = [
                'clientId' => 69573,
                'clientSecret' => '3bd7e053018564c39cc8da5f846a0d91954eded8',
                'redirectUri' => 'http://127.0.0.1:8000/callback'
            ];
            $oauth = new OAuth($options);
            $token = $oauth->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);

            print "<pre>";
            print_r($token);
            print "</pre>";

            print $token->getToken();
        } catch (Exception $e) {
            print $e->getMessage();
        }
    }

    public function showInfo()
    {
        try {
            $adapter = new \GuzzleHttp\Client(['base_uri' => 'https://www.strava.com/api/v3/']);
            $service = new REST('18872b47ef9f855c2df77d78ffd3bf0d16cd6a36', $adapter);  // Define your user token here.
            $client = new Client($service);

            $quoteClient = new QuoteClient();
            $quote = $quoteClient->getQuote();

//            $athlete = $client->getAthlete();
//            print "<pre>";
//            print_r($athlete);
//            print "</pre>";
//
//
//            $activities = $client->getAthleteActivities();
//            print "<pre>";
//            print_r($activities);
//            print "</pre>";

            $activity = $client->updateActivity('5887042812', $quote);
            print "<pre>";
            print_r($activity);
            print "</pre>";


        } catch (Exception $e) {
            print $e->getMessage();
        }
    }

    public function subscribeWebhook() {
        $adapter = new \GuzzleHttp\Client(['base_uri' => 'https://www.strava.com/api/v3/']);

        $response = $adapter->request('POST', 'push_subscriptions', [
            'form_params' => [
                'client_id' => '69573',
                'client_secret' => '3bd7e053018564c39cc8da5f846a0d91954eded8',
                'callback_url' => 'http://127.0.0.1:8000/webhook',
                'verify_token' => 'STRAVA',
            ]
        ]);
    }

    public function checkSubscribe() {
        $client = new \GuzzleHttp\Client(['base_uri' => 'https://www.strava.com/api/v3/']);
        $response = $client->request('GET', 'push_subscriptions?client_id=69573&client_secret=3bd7e053018564c39cc8da5f846a0d91954eded8');
        $body= json_decode($response->getBody(), JSON_PRETTY_PRINT);
        print "<pre>";
        print_r($body);
        print "</pre>";

        return $body;
    }
}
