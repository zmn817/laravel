<?php

namespace ThirtyThree\Tests\Unit\Sms;

use Sms;
use Cache;
use Tests\TestCase;

class SmsSendTest extends TestCase
{
    public function testSend()
    {
        Cache::flush();
        Sms::send(13888888888, ['content' => 'test message']);
        $this->assertTrue(true);
    }
}
