<?php


namespace App;


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
}
