<?php

namespace App\Http;

use App\Models\Activity;
use App\Models\Subscriber;
use App\Util;
use DateTime;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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
        Log::info("Begin create description");
        $type = $this->activity['type'];

        $description = "";
        if (strpos($this->activity['description'], "°C") === false && ($type === "Run" || $type === "Walk")) {
            // create weather text
            $util = new Util();
            $lat = $this->activity['start_latitude'];
            $lon = $this->activity['start_longitude'];
            $description = "🌤 Today's weather: " . $util->getWeatherInfo($lat, $lon);
        }

        if (strpos($this->activity['description'], "Today's quote") == false) {
            // create quote
            $quoteClient = new QuoteClient();
            $quote = $quoteClient->getQuote();
            $description .= "\n" . " 📜 Today's quote: " . $quote . "\n";
        }

        if ($type === "Run") {
            // create weather text
            $description .= "\n" . $this->createStats();
        }

        Log::info("Description created");
        Log::info($description);

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
            Log::info("Begin update");
            $adapter = new \GuzzleHttp\Client(['base_uri' => 'https://www.strava.com/api/v3/']);
            $service = new REST($this->access_token, $adapter);  // Define your user token here.
            $client = new Client($service);


            $description = $this->createDescription();
            Log::info($description);

            $activity = $client->updateActivity($this->activity_id, $this->createTitle(), null, null, null, null, null, $description);
            Log::info("Activity updated");
            Log::info($activity['name']);
            Log::info($activity['description']);
//            Log::info($activity);
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
//        $client = new \GuzzleHttp\Client(['base_uri' => 'https://www.strava.com/api/v3/']);
//        $response = $client->request('GET', 'push_subscriptions?client_id=69573&client_secret=3bd7e053018564c39cc8da5f846a0d91954eded8');
//        $response = $client->request('GET', 'athlete/activities?before=2021-09-30&after=2021-09-01');
//        $body= json_decode($response->getBody(), JSON_PRETTY_PRINT);
//        print "<pre>";
//        print_r($body);
//        print "</pre>";

        $adapter = new \GuzzleHttp\Client(['base_uri' => 'https://www.strava.com/api/v3/']);
        $service = new REST($this->access_token, $adapter);  // Define your user token here.
        $client = new Client($service);

        $activity = $client->getAthleteActivities(strtotime('2021-09-30T12:15:09Z'), strtotime('2021-09-01T12:15:09Z'), 2, 5);
        print "<pre>";
        print_r($activity);
        print "</pre>";

        return $activity;
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

        return json_decode($response->body());
    }

    public function createStats() {
        return $this->createThisWeekStats() . "\n\n" . $this->createThisMonthStats();
    }

    public function createThisWeekStats(): string
    {
        $this_week_stats = "💥💥💥 This Week Summary 💥💥💥";
        $start = (date('D') != 'Mon') ? date('Y-m-d', strtotime('last Monday')) : date('Y-m-d');
        $finish = (date('D') != 'Sun') ? date('Y-m-d', strtotime('next Sunday')) : date('Y-m-d');

        $stats = Activity::selectRaw('SUM(distance) as total_distance, MAX(distance) as longest_distance, AVG(distance) as average_distance, COUNT(id) as count, AVG(average_speed) as average_speed, SUM(total_elevation_gain) as total_climb, SUM(elapsed_time) as total_time')
            ->where('athlete_id', $this->athlete_id)
            ->where('type', 'Run')
            ->whereBetween('start_date', [$start, $finish])
            ->get();

        $stats = (new Util())->createActivityStats($stats[0]);
        $run_text = $stats['number_of_activity'] > 1 ? "runs" : "run";
        $this_week_stats .= "\n✅ Completed: " . $stats['number_of_activity'] . " $run_text";
        $this_week_stats .= "\n✅ Total Distance: " . $stats['total_distance'] . "km" . " (Avg.: " . $stats['average_distance'] . "km)";
        $this_week_stats .= "\n✅ Avg. Pace: " . $stats['average_pace'] . "min/km";
        $this_week_stats .= "\n✅ Total Climb: " . $stats['total_climb'] . "m (Avg.: " . $stats['average_climb'] . "m)";
        $this_week_stats .= "\n✅ Total Time: " . $stats['total_time']. "min";

        return $this_week_stats;
    }

    public function createThisMonthStats() {
        $this_month_stats = "\n⚡️⚡️⚡ ️This Month Summary ⚡️⚡️⚡️";
        $first_day_this_month = date('Y-m-01'); // hard-coded '01' for first day
        $last_day_this_month  = date('Y-m-t');

        $stats = Activity::selectRaw('SUM(distance) as total_distance, MAX(distance) as longest_distance, AVG(distance) as average_distance, COUNT(id) as count, AVG(average_speed) as average_speed, SUM(total_elevation_gain) as total_climb, SUM(elapsed_time) as total_time')
            ->where('athlete_id', 39375936)
            ->where('type', 'Run')
            ->whereBetween('start_date', [$first_day_this_month, $last_day_this_month])
            ->get();

        $stats = (new Util())->createActivityStats($stats[0]);
        $run_text = $stats['number_of_activity'] > 1 ? "runs" : "run";
        $this_month_stats .= "\n ✅Completed: " . $stats['number_of_activity'] . " $run_text";
        $this_month_stats .= "\n ✅Total Distance: " . $stats['total_distance'] . "km" . " (Avg.: " . $stats['average_distance'] . "km)";
        $this_month_stats .= "\n ✅Longest Run: " . $stats['longest_distance'] . "km";
        $this_month_stats .= "\n ✅Avg. Pace: " . $stats['average_pace'] . "min/km";
        $this_month_stats .= "\n ✅Total Climb: " . $stats['total_climb'] . "m (Avg.: " . $stats['average_climb'] . "m)";
        $this_month_stats .= "\n ✅Total Time: " . $stats['total_time']. "min";

        return $this_month_stats;
    }

    public function saveActivity()
    {
        Log::info("Begin save activity");
        $item = $this->activity;

        $now = new DateTime();
        $row = [
            'athlete_id' => $this->athlete_id,
            'activity_id' => $item['id'],
            'name' => $item['name'],
            'distance' => $item['distance'],
            'moving_time' => $item['moving_time'],
            'elapsed_time' => $item['elapsed_time'],
            'total_elevation_gain' => (int)$item['total_elevation_gain'],
            'type' => $item['type'],
            'workout_type' => isset($item['workout_type']) ? $item['workout_type'] : null,
            'start_date' => $item['start_date'],
            'start_date_local' => $item['start_date_local'],
            'timezone' => $item['timezone'],
            'location_city' => $item['location_city'],
            'location_state' => $item['location_state'],
            'location_country' => $item['location_country'],
            'achievement_count' => $item['achievement_count'],
            'kudos_count' => $item['kudos_count'],
            'comment_count' => $item['comment_count'],
            'athlete_count' => $item['athlete_count'],
            'manual' => $item['manual'],
            'private' => $item['private'],
            'average_speed' => $item['average_speed'],
            'max_speed' => $item['max_speed'],
            'average_cadence' => isset($item['average_cadence']) ? $item['average_cadence'] : null,
            'has_heartrate' => $item['has_heartrate'],
            'average_heartrate' => isset($item['average_heartrate']) ? $item['average_heartrate'] : null,
            'max_heartrate' => isset($item['max_heartrate']) ? $item['max_heartrate'] : null,
            'pr_count' => $item['pr_count'],
            'created_at' => $now->format('Y-m-d H:i:s'),
            'updated_at' => $now->format('Y-m-d H:i:s'),
        ];

        DB::table('activities')->insert($row);

        Log::info("Activity saved");
        return 0;
    }
}
