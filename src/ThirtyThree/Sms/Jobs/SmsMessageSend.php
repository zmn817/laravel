<?php

namespace ThirtyThree\Sms\Jobs;

use SmsEasy;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;

class SmsMessageSend implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $to;
    public $message;
    public $gateways;

    public function __construct(PhoneNumberInterface $to, $message, array $gateways = [])
    {
        $this->to = $to;
        $this->message = $message;
        $this->gateways = $gateways;
    }

    public function handle()
    {
        SmsEasy::send($this->to, $this->message, $this->gateways);
    }

    public function tags()
    {
        return ['sms', 'notification'];
    }
}
