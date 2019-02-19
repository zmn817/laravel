<?php

namespace ThirtyThree\Geetest;

use Cache;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class Geetest
{
    public function challenge($verify, $extra = [])
    {
        $config = $this->config($verify);
        $api = new Api();
        try {
            $challenge = $api->challenge($config['id'], $extra);
        } catch (\Exception $e) {
            $challenge = null;
        }

        if (strlen($challenge) == 32) {
            // 在线
            $geetest_status = 1;
            $challenge = $this->success($config, $challenge);
        } else {
            // 离线
            $geetest_status = 2;
            $challenge = $this->failback($config['id']);
        }
        $id = Uuid::uuid4();
        Cache::put('verification-code:'.$id, [
            'verify' => $config['verify'],
            'driver' => 'geetest',
            'status' => $geetest_status,
        ], 60);
        $challenge['id'] = $id;

        return compact('config', 'challenge');
    }

    public function verify($id, $challenge, $validate, $seccode, $extra = [])
    {
        $cacheKey = 'verification-code:'.$id;
        $info = Cache::get($cacheKey);
        if (empty($info) || ! is_array($info)) {
            return false;
        }
        $driver = array_get($info, 'driver');
        if ($driver !== 'geetest') {
            return false;
        }

        $status = array_get($info, 'status');
        $verify = array_get($info, 'verify');
        $result = false;
        if ($status == 1) {
            $config = $this->config($verify);
            if (md5($config['key'].'geetest'.$challenge) != $validate) {
                return false;
            }

            $api = new Api();
            try {
                $res = $api->verify($config['id'], $challenge, $seccode);
            } catch (\Exception $e) {
                return false;
            }
            $json = json_decode($res, true);

            $result = md5($seccode) == array_get($json, 'seccode');
        } elseif ($status == 2) {
            $result = md5($challenge) == $validate;
        }

        return $result;
    }

    protected function success($config, $challenge)
    {
        $challenge = md5($challenge.$config['key']);

        return [
            'success' => 1,
            'gt' => $config['id'],
            'challenge' => $challenge,
            'new_captcha' => 1,
        ];
    }

    protected function failback($captcha_id)
    {
        $rand1 = md5(rand(0, 100));
        $rand2 = md5(rand(0, 100));
        $challenge = $rand1.substr($rand2, 0, 2);

        return [
            'success' => 0,
            'gt' => $captcha_id,
            'challenge' => $challenge,
            'new_captcha' => 1,
        ];
    }

    protected function config($verify)
    {
        $config = array_merge(['verify' => $verify], config('verificationcode.verifies.'.$verify, []));
        if (array_get($config, 'driver') !== 'geetest' || empty($config['id']) || empty($config['key'])) {
            throw new RuntimeException('Geetest config error');
        }

        return $config;
    }
}
