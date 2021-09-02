<?php
namespace App\Console\Commands;

use App\Services\StravaWebhookService;
use Illuminate\Console\Command;

class UnsubscribeStravaWebhookCommand extends Command
{
    protected $signature = 'strava:unsubscribe';

    protected $description = 'Deletes a Strava webhook subscription';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if (app(StravaWebhookService::class)->unsubscribe()) {
            $this->info("Successfully unsubscribed");
        } else {
            $this->warn('Error or no subscription found');
        }

        return 0;
    }
}
