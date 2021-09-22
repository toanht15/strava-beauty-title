<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Subscriber;
use DateTime;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Strava\API\Client;
use Strava\API\Service\REST;

class GetAthleteActivites extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'strava:get-activities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Athlete Activities';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $athlete_id = 39375936;
            $adapter = new \GuzzleHttp\Client(['base_uri' => 'https://www.strava.com/api/v3/']);
            $subscriber = $this->update_tokens(Subscriber::find($athlete_id));
            $service = new REST($subscriber->access_token, $adapter);  // Define your user token here.
            $client = new Client($service);

            $data = [];
            $this->info("abc");
            for ($i = 1; $i<=1000; $i++) {
                $this->info($i);
                $activity = $client->getAthleteActivities(strtotime('2021-09-30'), strtotime('2021-01-01'), $i, 30);

                if (count($activity) === 0) break;

                foreach ($activity as $item) {
                    $now = new DateTime();
                    $row = [
                        'athlete_id' => $athlete_id,
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

                    array_push($data, $row);
                }

            }

            DB::table('activities')->insert($data);
            $this->info("OK");
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }

        return 0;
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

                return $subscriber;
            }

            return $tokens;
        } catch (\Strava\API\Exception $e) {
            print $e->getMessage();
            Log::error($e->getMessage());
        }

    }
}
