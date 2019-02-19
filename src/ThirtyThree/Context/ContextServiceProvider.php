<?php

namespace ThirtyThree\Context;

use Illuminate\Http\Request;
use TimJuly\Context\Context;
use Illuminate\Support\ServiceProvider;

class ContextServiceProvider extends ServiceProvider
{
    protected $defer = false;

    /**
     * Register services.
     */
    public function register()
    {
        $this->app->singleton('context', function ($app) {
            $request = app(Request::class);

            return new Context($app, $request);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
    }
}
