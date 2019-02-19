<?php

namespace ThirtyThree\Validation;

use Cache;
use Illuminate\Http\Request;
use ThirtyThree\Geetest\Geetest;

class Validator
{
    public static function verify()
    {
        $id = request()->input('verificationcode_id');
        $driver = request()->input('verificationcode_driver');
        if (empty($id) || empty($driver)) {
            return false;
        }
        switch ($driver) {
            case 'image':
                return self::image($id);
            case 'geetest':
                return self::geetest($id);
        }

        return false;
    }

    protected static function image($id)
    {
        $info = Cache::pull('verification-code:'.$id);
        if (empty($info) || ! is_array($info)) {
            return false;
        }
        $driver = array_get($info, 'driver');
        if ($driver !== 'image') {
            return false;
        }

        $parse = array_get($info, 'parse');
        if (empty($parse)) {
            return false;
        }

        $str = request()->input('verificationcode_code');

        return strtolower($str) === strtolower($parse);
    }

    protected static function geetest($id)
    {
        $data = request()->only(['geetest_challenge', 'geetest_validate', 'geetest_seccode']);
        if (empty($data['geetest_challenge']) || empty($data['geetest_validate']) || empty($data['geetest_seccode'])) {
            return false;
        }
        $geetest = new Geetest();
        $extra = [
            'ip_address' => request()->ip(),
        ];

        try {
            return $geetest->verify($id, $data['geetest_challenge'], $data['geetest_validate'], $data['geetest_seccode']);
        } catch (\Exception $e) {
            return false;
        }
    }
}
