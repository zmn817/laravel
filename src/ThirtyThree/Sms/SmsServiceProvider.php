<?php

namespace ThirtyThree\Sms;

use Overtrue\EasySms\EasySms;
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
    }

    private function registerSms()
    {
        $this->app->singleton('sms', function () {
            return new SmsManager();
        });

        $this->app->singleton('sms.easy', function ($app) {
            return new EasySms($app['config']->get('sms'));
        });
    }
}
