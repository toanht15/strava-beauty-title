<?php
namespace App\Console\Commands;

use App\Services\StravaWebhookService;
use Illuminate\Console\Command;

class ViewStravaWebhookCommand extends Command
{
    protected $signature = 'strava:view-subscription';

    protected $description = 'Views a Strava webhook subscription';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $id = app(StravaWebhookService::class)->view();

        if ($id) {
            $this->info("Subscription ID: $id");
        } else {
            $this->warn('Error or no subscription found');
        }

        return 0;
    }
}
