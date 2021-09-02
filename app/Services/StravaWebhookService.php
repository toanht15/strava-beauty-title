<?php
namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class StravaWebhookService
{
    private $client_id;
    private $client_secret;
    private $url;
    private $callback_url;
    private $verify_token;

    public function __construct()
    {
        $this->client_id = env('STRAVA_CLIENT_ID');
        $this->client_secret = env('STRAVA_CLIENT_SECRET');
        $this->url = config('services.strava.push_subscriptions_url');
        $this->callback_url = config('services.strava.webhook_callback_url');
        $this->verify_token = config('services.strava.webhook_verify_token');
    }

    public function subscribe()
    {
        $response = Http::post($this->url, [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'callback_url' => $this->callback_url,
            'verify_token' => $this->verify_token,
        ]);

        if ($response->status() === Response::HTTP_CREATED) {
            return json_decode($response->body())->id;
        }

        return $response;
    }

    public function unsubscribe()
    {
        $id = app(StravaWebhookService::class)->view(); // use the singleton

        if (!$id) {
            return false;
        }

        $response = Http::delete("$this->url/$id", [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
        ]);

        if ($response->status() === Response::HTTP_NO_CONTENT) {
            return true;
        }

        return false;
    }

    public function view()
    {
        $response = Http::get($this->url, [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
        ]);

        if ($response->status() === Response::HTTP_OK) {
            $body = json_decode($response->body());

            if ($body) {
                return $body[0]->id; // each application can have only 1 subscription
            } else {
                return null; // no subscription found
            }
        }

        return null;
    }

    public function validate(string $mode, string $token, string $challenge)
    {
        if ($mode && $token) {
            // Verifies that the mode and token sent are valid
            if ($mode === 'subscribe' && $token === $this->verify_token) {
                // Responds with the challenge token from the request
                return response()->json(['hub.challenge' => $challenge]);
            } else {
                // Responds with '403 Forbidden' if verify tokens do not match
                return response('', Response::HTTP_FORBIDDEN);
            }
        }

        return response('', Response::HTTP_FORBIDDEN);
    }
}
