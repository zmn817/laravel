<?php

namespace ThirtyThree\Storage;

use Illuminate\Support\ServiceProvider;

class StorageServiceProvider extends ServiceProvider
{
    protected $defer = false;

    public function register()
    {
        $this->registerStorage();
    }

    private function registerStorage()
    {
        $this->app->singleton('timjuly.storage', function ($app) {
            return new StorageManager($app);
        });
    }
}
