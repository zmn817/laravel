<?php

namespace ThirtyThree\Support\Facades;

use Illuminate\Support\Facades\Facade;

class Storage extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'timjuly.storage';
    }
}
