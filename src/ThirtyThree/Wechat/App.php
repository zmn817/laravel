<?php

namespace ThirtyThree\Wechat;

use RuntimeException;
use EasyWeChat\Factory;
use App\Models\WechatApp;

class App
{
    protected $info;
    protected $app;

    public function __construct(WechatApp $account, $config = [])
    {
        $this->info = $account;

        $defaultConfig = $this->parseConfig($account);
        $config = array_merge($defaultConfig, $config);

        switch ($account->type) {
            case 1:
            case 2:
                $this->app['officialAccount'] = Factory::make('officialAccount', $config);
                break;
            case 11:
                $this->app['miniProgram'] = Factory::make('miniProgram', $config);
                break;
        }

        if (! empty($account->payment_merchant_id)) {
            $this->app['payment'] = Factory::make('payment', $config);
        }
    }

    public function __get($name)
    {
        if ($name == 'info') {
            return $this->info;
        }

        if ($name == 'app') {
            switch ($this->info->type) {
                case 1:
                case 2:
                    return $this->officialAccount;
                case 11:
                    return $this->miniProgram;
            }
        }

        if (! empty($this->app[$name])) {
            return $this->app[$name];
        }

        throw new RuntimeException("Wechat has no property of [{$name}]");
    }

    protected function parseConfig($account)
    {
        return [
            'debug' => config('app.debug'),
            'log' => [
                'level' => config('app.debug') ? 'debug' : 'critical',
                'file' => storage_path('logs/wechat/'.$account->original_id.'-common-'.date('Y-m-d').'.log'),
            ],
            // 通用
            'app_id' => $account->app_id,
            'secret' => $account->secret,
            'token' => $account->token,
            'aes_key' => $account->aes_key,
            // 支付
            'mch_id' => $account->payment_merchant_id,
            'key' => $account->payment_key,
            'cert_path' => $account->payment_cert_path,
            'key_path' => $account->payment_key_path,
        ];
    }
}
