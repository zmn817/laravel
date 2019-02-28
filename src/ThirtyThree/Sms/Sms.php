<?php

namespace ThirtyThree\Sms;

use Illuminate\Support\Arr;
use Overtrue\EasySms\EasySms;
use ThirtyThree\Sms\Models\SmsMessage;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;

class Sms
{
    protected $sms;

    public function __construct($config)
    {
        $this->sms = new EasySms($config);
    }

    public function send(PhoneNumberInterface $to, array $message, array $gateways = [])
    {
        $info = [
            'country_code' => $to->getIDDCode(),
            'phone_number' => $to->getNumber(),
            'content' => Arr::get($message, 'content'),
            'template' => Arr::get($message, 'template'),
            'data' => Arr::get($message, 'data'),
        ];
        try {
            $result = $this->sms->send($to, $message, $gateways);
        } catch (\Exception $e) {
            $exceptions = [];
            foreach ($e->getExceptions() as $gateway => $exception) {
                $exceptions[] = [
                    'gateway' => $gateway,
                    'exception' => $exception->getMessage(),
                ];
            }
            SmsMessage::create($info + [
                'success' => false,
                'errors' => $exceptions,
            ]);
            throw $e;
        }

        SmsMessage::create($info + [
            'success' => true,
        ]);

        return $result;
    }
}
