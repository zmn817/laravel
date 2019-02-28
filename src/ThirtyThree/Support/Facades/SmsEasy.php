<?php

namespace ThirtyThree\Support\Facades;

use Illuminate\Support\Facades\Facade;

class SmsEasy extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'sms.easy';
    }
}
