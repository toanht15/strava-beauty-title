<?php


namespace App;


use GuzzleHttp\Client;
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
        $response = $client->request('GET', 'onecall/timemachine', $query);
        $icons = ['01d' => '🌄', '01n' => '🌙', '02d' => '🌤', '02n' => '☁', '03d' => '☁', '03n' => '☁',
             '04d' => '🌥', '04n' => '🌥', '50d' => '🌫', '50n' => '🌫', '13d' => '🌨', '13n' => '🌨',
             '10n' => '🌧', '10d' => '🌦', '09d' => '🌧', '09n' => '🌧', '11d' => '⛈', '11n' => '⛈'];
        $info = json_decode($response->getBody(), JSON_PRETTY_PRINT);

        $icon_code = $info['current']['weather'][0]['icon'];
        $description = ucfirst($info['current']['weather'][0]['description']);
        $weather_description = "🌡 " . $info['current']['temp'] . "°C, Feels like " . $info['current']['feels_like'] . "°C, " . "💦 " . $info['current']['humidity'] . "%, 💨 " . $info['current']['wind_speed'] . "m/s" ;
        return $icons[$icon_code] . $description . "-" . $weather_description;
    }
}
