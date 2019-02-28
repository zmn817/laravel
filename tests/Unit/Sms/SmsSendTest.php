<?php

namespace ThirtyThree\Tests\Unit\Sms;

use Sms;
use Cache;
use Tests\TestCase;
use RuntimeException;

class SmsSendTest extends TestCase
{
    public function testSendOnce()
    {
        Cache::flush();
        Sms::send(13888888888, ['content' => 'test message']);
        $this->assertTrue(true);
    }

    public function testSendTwiceInOneMinute()
    {
        Cache::flush();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('超过短信发送频率限制');
        Sms::send(13888888888, ['content' => 'test message']);
        Sms::send(13888888888, ['content' => 'test message']);
    }

    public function testForceSendMultipleInOneMinute()
    {
        Cache::flush();
        Sms::send(13888888888, ['content' => 'test message']);
        Sms::forceSend(13888888888, ['content' => 'test message']);
        Sms::forceSend(13888888888, ['content' => 'test message']);
        $this->assertTrue(true);
    }
}
