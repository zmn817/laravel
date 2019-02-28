<?php

namespace ThirtyThree\Sms;

use Cache;
use Carbon\Carbon;
use RuntimeException;
use Overtrue\EasySms\PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use ThirtyThree\Sms\Jobs\SmsMessageSend;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;

class SmsManager
{
    public function send($to, $message, $providers = [])
    {
        $to = $this->format($to);
        $to = new PhoneNumber($to['phone_number'], $to['country_code']);

        // 1分钟内限制发送短信1次
        $checkKey = 'sms_send_'.json_encode($to);
        if (Cache::has($checkKey)) {
            throw new RuntimeException('超过短信发送频率限制');
        }
        $expiresAt = Carbon::now()->addSeconds(55);
        Cache::put($checkKey, $expiresAt, $expiresAt);

        $job = new SmsMessageSend($to, $message, $providers);

        return dispatch($job);
    }

    public function forceSend($to, $message, $providers = [])
    {
        $to = $this->format($to);
        $to = new PhoneNumber($to['phone_number'], $to['country_code']);

        $job = new SmsMessageSend($to, $message, $providers);

        return dispatch($job);
    }

    public function format($to)
    {
        if (is_array($to)) {
            if (! array_key_exists('country_code', $to) || ! array_key_exists('phone_number', $to)) {
                throw new RuntimeException('Wrong format of the phone to send');
            }
            $country_code = $to['country_code'];
            $phone_number = $to['phone_number'];
        } elseif ($to instanceof PhoneNumberInterface) {
            $country_code = $to->getIDDCode() ?: 86;
            $phone_number = $to->getNumber();
        } elseif ($to instanceof Model) {
            $country_code = $to->country_code ?: 86;
            $phone_number = $to->phone_number;
        } elseif (is_string($to) || is_numeric($to)) {
            $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
            $number = $phoneUtil->parse($to, 'CN');
            $country_code = $number->getCountryCode();
            $fullNumber = $phoneUtil->format($number, \libphonenumber\PhoneNumberFormat::E164);
            $phone_number = substr($fullNumber, count($country_code) + 2);
        } else {
            throw new RuntimeException('Unsupport format of the phone to send');
        }

        return compact('country_code', 'phone_number');
    }
}
