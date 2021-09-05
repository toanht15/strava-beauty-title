<?php

namespace App\Http;

use App\Models\Subscriber;
use App\Util;
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
    protected $activity;

    public function __construct($athlete_id, $activity_id) {
        $this->athlete_id = $athlete_id;
        $this->activity_id = $activity_id;
        $tokens = Subscriber::find($athlete_id);
        $tokens = $this->update_tokens($tokens);
        $this->access_token = $tokens->access_token;
        $this->refresh_token = $tokens->refresh_token;
        $this->activity = $this->getActivity();
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
            $service = new REST($this->access_token, $adapter);  // Define your user token here.
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

    public function getActivity()
    {
        $adapter = new \GuzzleHttp\Client(['base_uri' => 'https://www.strava.com/api/v3/']);
        $service = new REST($this->access_token, $adapter);  // Define your user token here.
        $client = new Client($service);

        $activity = $client->getActivity($this->activity_id);

        return $activity;
    }

    public function createDescription()
    {
        $type = $this->activity['type'];

        $description = "";
        if (strpos($this->activity['description'], "°C") == false && $type == "Run") {
            // create weather text
            $util = new Util();
            $lat = $this->activity['start_latitude'];
            $lon = $this->activity['start_longitude'];
            $description = "Today's weather: " . $util->getWeatherInfo($lat, $lon);
        }

        if (strpos($this->activity['description'], "Today's quote") == false) {
            // create quote
            $quoteClient = new QuoteClient();
            $quote = $quoteClient->getQuote();
            $description .= "\n" . "Today's quote: " . $quote . "\n";
        }

        return $description . $this->activity['description'];
    }

    public function createTitle()
    {
        $numbers = [
          0 => "0️⃣",
          1 => "1️⃣",
          2 => "2️⃣",
          3 => "3️⃣",
          4 => "4️⃣",
          5 => "5️⃣",
          6 => "6️⃣",
          7 => "7️⃣",
          8 => "8️⃣",
          9 => "9️⃣",
          10=> "🔟"
        ];
        $type = $this->activity['type'];
        $time = $this->activity['elapsed_time'];
        if ($time < 3600) {
            $hour = 0;
            $min = intdiv($time, 60);
            $time_text = $min . "min";
        } else {
            $hour = intdiv($time, 3600);
            $min = intdiv($time % 3600, 60);
            $time_text = $hour . "h". $min . "min";
        }
        $cal = (int)$this->activity['calories'];

        switch ($type) {
            case "Run":
                $distance = $this->activity['distance'];
                if ($distance < 1000) {
                    $distance_text = $numbers[1] ."km";
                } else {
                    $long_distance = intdiv($distance, 1000);
                    if ($long_distance <= 10) {
                        $distance_text = $numbers[$long_distance] . "km";
                    } else {
                        $distance_text =  $numbers[intdiv($long_distance, 10)] . $numbers[$long_distance % 10] . "km";
                    }
                }

                return date("l") . " 🏃 Running " . $distance_text . " 🕒" . $time_text . " 🔥" . $cal . "cal";
            case "Walk":
                $distance = $this->activity['distance'];
                if ($distance < 1000) {
                    $distance_text = $numbers[1] ."km";
                } else {
                    $long_distance = intdiv($distance, 1000);
                    if ($long_distance <= 10) {
                        $distance_text = $numbers[$long_distance] . "km";
                    } else {
                        $distance_text =  $numbers[intdiv($long_distance, 10)] . $numbers[$long_distance % 10] . "km";
                    }
                }

                return date("l") ." 🚶 Walking - " . $distance_text . " 🕒" . $time_text . " 🔥" . $cal . "cal";
            case "Workout":
                return date("l") . " 🏋🏻 Cardio‍️" . " 🕒" . $time_text . " 🔥" . $cal . "cal";
            case "WeightTraining":
                return date("l") . "🏋🏻 Gym" . " 🕒" . $time_text . " 🔥" . $cal . "cal";
            default:
                return "Activity";
        }
    }

    public function updateActivity()
    {
        try {
            Log::channel('slack')->info("Start update activity");
            $adapter = new \GuzzleHttp\Client(['base_uri' => 'https://www.strava.com/api/v3/']);
            $service = new REST($this->access_token, $adapter);  // Define your user token here.
            $client = new Client($service);


            $description = $this->createDescription();

            $activity = $client->updateActivity($this->activity_id, $this->createTitle(), null, null, null, null, null, $description);

            Log::info($activity);
            return $activity;
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
