<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\StravaWebhookService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(StravaWebhookService::class, function ($app) {
            return new StravaWebhookService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
