<?php

namespace App\Http;

use App\Models\Subscriber;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Provider\AbstractProvider;
use Strava\API\OAuth;
use Strava\API\Exception;

use Strava\API\Client;
use Strava\API\Service\REST;


class StravaClient
{
    protected $athlete_id;
    protected $activity_id;
    protected $access_token;
    protected $refresh_token;

    public function __construct($athlete_id, $activity_id) {
        $this->athlete_id = $athlete_id;
        $this->activity_id = $activity_id;
        $tokens = Subscriber::find($athlete_id);
        $tokens = $this->update_tokens($tokens);
        $this->access_token = $tokens->access_token;
        $this->refresh_token = $tokens->refresh_token;
//        print "<pre>";
//        print_r($tokens->access_token);
//        print "</pre>";
    }

    public function update_tokens($tokens) {
        try {
            if ($tokens->expires_at < time()) {
                $adapter = new \GuzzleHttp\Client(['base_uri' => 'https://www.strava.com/api/v3/']);
                $params = [
                    'client_id' => env('STRAVA_CLIENT_ID'),
                    'client_secret' => env('STRAVA_CLIENT_SECRET'),
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $tokens->refresh_token,
                ];

                $response = $adapter->request('POST', 'oauth/token', [
                    'form_params' => $params
                ]);

                $response_decoded = json_decode($response->getBody(), JSON_PRETTY_PRINT);
                $subscriber = Subscriber::find($tokens->id);
                $subscriber->access_token = $response_decoded['access_token'];
                $subscriber->refresh_token = $response_decoded['refresh_token'];
                $subscriber->expires_at = $response_decoded['expires_at'];
                $subscriber->save();

            print "<pre>";
            print_r(json_decode($response->getBody(), JSON_PRETTY_PRINT));
            print "</pre>";
                return $subscriber;
            }

            return $tokens;
        } catch (Exception $e) {
            print $e->getMessage();
            Log::error($e->getMessage());
        }

    }

    public function auth()
    {
        try {
            $options = [
                'clientId' => env('STRAVA_CLIENT_ID'),
                'clientSecret' => env('STRAVA_CLIENT_SECRET'),
                'redirectUri' => env('STRAVA_AUTH_REDIRECT_URL')
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
            Log::error($e->getMessage());
        }
    }

    public function getToken($code)
    {
        try {
            $options = [
                'clientId' => env('STRAVA_CLIENT_ID'),
                'clientSecret' => env('STRAVA_CLIENT_SECRET'),
                'redirectUri' => env('STRAVA_AUTH_REDIRECT_URL')
            ];
            $oauth = new OAuth($options);
            $response = $oauth->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);

//            print "<pre>";
//            print_r($response->jsonSerialize());
//            print "</pre>";
            return $response->jsonSerialize();
        } catch (Exception $e) {
            print $e->getMessage();
        }
    }

    public function showInfo()
    {
        try {
            $adapter = new \GuzzleHttp\Client(['base_uri' => 'https://www.strava.com/api/v3/']);
            $service = new REST(env('STRAVA_ACCESS_TOKEN'), $adapter);  // Define your user token here.
            $client = new Client($service);

            $quoteClient = new QuoteClient();
            $quote = $quoteClient->getQuote();
            Log::channel('slack')->info($quote);

            $activity = $client->updateActivity('5891825013', $quote);
            Log::channel('slack')->info($activity);
        } catch (Exception $e) {
            Log::channel('slack')->error($e->getMessage());
        }
    }

    public function updateActivityTitle()
    {
        try {
            Log::channel('slack')->info("Start update activity");
            $adapter = new \GuzzleHttp\Client(['base_uri' => 'https://www.strava.com/api/v3/']);
            $service = new REST($this->access_token, $adapter);  // Define your user token here.
            $client = new Client($service);

            $quoteClient = new QuoteClient();
            $quote = $quoteClient->getQuote();

            $activity = $client->updateActivity($this->activity_id, $quote);
            Log::info($quote);
            Log::info($activity);
        } catch (Exception $e) {
            Log::channel('slack')->error($e->getMessage());
            Log::error($e->getMessage());
        }
    }

    public function subscribeWebhook() {
        $adapter = new \GuzzleHttp\Client(['base_uri' => 'https://www.strava.com/api/v3/']);

        $response = $adapter->request('POST', 'push_subscriptions', [
            'form_params' => [
                'client_id' => env('STRAVA_CLIENT_ID'),
                'client_secret' => env('STRAVA_CLIENT_SECRET'),
                'callback_url' => env('STRAVA_WEBHOOK_CALLBACK_URL'),
                'verify_token' => env('STRAVA_WEBHOOK_VERIFY_TOKEN'),
            ]
        ]);

        if ($response->getStatusCode() === Response::HTTP_CREATED) {
            return json_decode($response->body())->id;
        } else {
            return $response;
        }
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

    public function refreshToken() {
        $adapter = new \GuzzleHttp\Client(['base_uri' => 'https://www.strava.com/api/v3/']);

        $response = $adapter->request('POST', 'oauth/token', [
            'form_params' => [
                'client_id' => env('STRAVA_CLIENT_ID'),
                'client_secret' => env('STRAVA_CLIENT_SECRET'),
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refresh_token,
            ]
        ]);

        print "<pre>";
        print_r(json_decode($response->body()));
        print "</pre>";

        return json_decode($response->body());

    }
}
