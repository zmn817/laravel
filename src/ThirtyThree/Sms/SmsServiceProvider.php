<?php

namespace ThirtyThree\Sms;

use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    protected $defer = false;

    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/sms.php' => config_path('sms.php'),
        ]);
    }

    public function register()
    {
        $this->registerSms();

        $this->loadMigrationsFrom(__DIR__.'/migrations');
    }

    private function registerSms()
    {
        $this->app->singleton('sms', function ($app) {
            return new Sms($app['config']->get('sms'));
        });
    }
}
