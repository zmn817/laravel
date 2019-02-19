<?php

namespace ThirtyThree\Qiniu;

use Illuminate\Support\ServiceProvider;

class QiniuServiceProvider extends ServiceProvider
{
    protected $defer = false;

    public function register()
    {
        $this->registerQiniu();
    }

    private function registerQiniu()
    {
        $this->app->singleton('qiniu', function ($app) {
            return new QiniuManager($app);
        });
    }
}
