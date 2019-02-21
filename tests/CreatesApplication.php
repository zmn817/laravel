<?php

namespace ThirtyThree\Tests;

use Illuminate\Contracts\Console\Kernel;
use ThirtyThree\Context\ContextServiceProvider;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../vendor/laravel/laravel/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        $app->useStoragePath(__DIR__.'/../storage');
        $app->register(new ContextServiceProvider($app));

        require __DIR__.'/config.php';

        return $app;
    }
}
