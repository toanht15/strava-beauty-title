<?php

namespace App\Console\Commands;

use App\Services\StravaWebhookService;
use Illuminate\Console\Command;

class SubscribeToStravaWebhookCommand extends Command
{
    protected $signature = 'strava:subscribe';

    protected $description = 'Subscribes to a Strava webhook';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $id = app(StravaWebhookService::class)->subscribe();

        if ($id) {
            $this->info("Successfully subscribed ID: {$id}");
        } else {
            $this->warn('Unable to subscribe');
        }

        return 0;
    }
}
