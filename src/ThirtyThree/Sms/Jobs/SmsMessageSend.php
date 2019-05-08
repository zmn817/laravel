<?php

namespace ThirtyThree\Sms\Jobs;

use SmsEasy;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

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
        try {
            SmsEasy::send($this->to, $this->message, $this->gateways);
        } catch (NoGatewayAvailableException $e) {
            $exceptions = $e->getExceptions();
            foreach ($exceptions as $channel => $exception) {
                \Log::error(sprintf(
                        "短信发送失败 %s %s\n%s",
                        $channel,
                        $exception->getMessage(),
                        $exception->getTraceAsString()
                    )
                );
            }

            throw $e;
        }
    }

    public function tags()
    {
        return ['sms', 'notification'];
    }
}
