<?php


namespace App;


use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Provider\AbstractProvider;
use Strava\API\Exception;
use Strava\API\OAuth;

class Util
{
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

            return $response->jsonSerialize();
        } catch (Exception $e) {
            print $e->getMessage();
            Log::error($e->getMessage());
        }
    }

    public function makeAuthLink() {
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
                    'activity:write',
                    'activity:read_all'
                    // 'write',
                    // 'view_private',
                ]
            ]) . '">Connect</a>';
    }

    public function getWeatherInfo($lat, $lon)
    {
        $client = new Client(['base_uri' => 'http://api.openweathermap.org/data/2.5/']);
        $weather_api_key = env('API_WEATHER_KEY');
        $query = [
            'query' =>
                [
                    'lat' => $lat,
                    'lon' => $lon,
                    'appid' => $weather_api_key,
                    'dt' => time(),
                    'units' => 'metric',
                    'lang' => 'en'
                ]
        ];
        Log::info($query);
        $response = $client->request('GET', 'onecall/timemachine', $query);
        $icons = ['01d' => '🌄', '01n' => '🌙', '02d' => '🌤', '02n' => '☁', '03d' => '☁', '03n' => '☁',
             '04d' => '🌥', '04n' => '🌥', '50d' => '🌫', '50n' => '🌫', '13d' => '🌨', '13n' => '🌨',
             '10n' => '🌧', '10d' => '🌦', '09d' => '🌧', '09n' => '🌧', '11d' => '⛈', '11n' => '⛈'];
        $info = json_decode($response->getBody(), JSON_PRETTY_PRINT);

        $icon_code = $info['current']['weather'][0]['icon'];
        $description = ucfirst($info['current']['weather'][0]['description']);
        $weather_description = "🌡 " . $info['current']['temp'] . "°C, Feels like " . $info['current']['feels_like'] . "°C, " . "💦 Humidity " . $info['current']['humidity'] . "%, 💨 Wind " . $info['current']['wind_speed'] . "m/s" ;
        return $icons[$icon_code] . $description . "-" . $weather_description;
    }

    public function createActivityStats($input): array
    {
        $total_distance = round($input['total_distance'] / 1000, 1);
        $longest_distance = round($input['longest_distance'] / 1000, 1);
        $average_distance = round($input['average_distance'] / 1000, 1);
        $total_time = (int)($input['total_time'] / 60);
        $average_pace_sendconds = (int)(1000 / $input['average_speed']);
        $average_pace = (int)($average_pace_sendconds / 60) . "." . $average_pace_sendconds % 60;
        $average_climb = (int)($input['total_climb'] / $input['count']);

        $data = [
            'number_of_activity' => $input['count'],
            'total_distance' => $total_distance,
            'longest_distance' => $longest_distance,
            'average_distance' => $average_distance,
            'total_climb' => $input['total_climb'],
            'total_time' => $total_time,
            'average_pace' => $average_pace,
            'average_climb' => $average_climb
        ];

        return $data;
    }
}
