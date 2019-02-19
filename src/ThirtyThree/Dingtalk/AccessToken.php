<?php

namespace ThirtyThree\Dingtalk;

use Cache;
use RuntimeException;

class AccessToken
{
    public static function token()
    {
        $config = config('services.dingtalk_corp');
        if (empty($config)) {
            throw new RuntimeException('Dingtalk not configured');
        }

        return Cache::remember('dingtalk_token_'.$config['client_id'], 100, function () use ($config) {
            return self::getToken($config);
        });
    }

    protected static function getToken($config)
    {
        return with(new Api())->token($config['client_id'], $config['client_secret'])['access_token'];
    }

    public static function snsToken()
    {
        $config = config('services.dingtalk');
        if (empty($config)) {
            throw new RuntimeException('Dingtalk not configured');
        }

        return Cache::remember('dingtalk_token_'.$config['client_id'], 100, function () use ($config) {
            return self::getSnsToken($config);
        });
    }

    protected static function getSnsToken($config)
    {
        return with(new Api())->snsToken($config['client_id'], $config['client_secret'])['access_token'];
    }
}
