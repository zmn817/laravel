<?php

namespace ThirtyThree\Wechat;

use App\Models\WechatApp;

class WechatApplication
{
    protected static $apps = [];

    public static function make($id = null, $config = [])
    {
        if (is_null($id)) {
            $account = WechatApp::first();
        } else {
            $account = WechatApp::find($id);
        }

        if (empty($account)) {
            throw new \RuntimeException('Wechat app not configured', 404);
        }

        if (! empty(self::$apps[$account->id])) {
            return self::$apps[$account->id];
        }

        $app = new App($account, $config);
        self::$apps[$account->id] = $app;

        return $app;
    }

    public static function isWechatBrowser($request)
    {
        return strpos($request->header('user_agent'), 'MicroMessenger') !== false;
    }
}
