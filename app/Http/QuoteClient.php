<?php


namespace App\Http;
use GuzzleHttp\Client;

class QuoteClient
{
    public function getQuote() {
        $client = new Client(['base_uri' => 'https://api.quotable.io/']);
        $response = $client->request('GET', 'random?maxLength=50');
        $body= json_decode($response->getBody(), JSON_PRETTY_PRINT);

        return $body['content'];
    }
}
