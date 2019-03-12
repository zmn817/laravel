<?php

namespace ThirtyThree\Tests\Unit\Sms;

use Sms;
use TestApp\User;
use RuntimeException;
use ThirtyThree\Tests\TestCase;
use Overtrue\EasySms\PhoneNumber;

class SmsFormatTest extends TestCase
{
    public function testArrayNormal()
    {
        $number = Sms::format(['country_code' => 86, 'phone_number' => 13888888888]);
        $this->assertEquals(['country_code' => 86, 'phone_number' => 13888888888], $number);
    }

    public function testArrayEmpty()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Wrong format of the phone to send');
        Sms::format([]);
    }

    public function testformatPhoneNumber()
    {
        $phoneNumber = new PhoneNumber(13888888888, 86);
        $number = Sms::format($phoneNumber);
        $this->assertEquals(['country_code' => 86, 'phone_number' => 13888888888], $number);
    }

    public function testformatFromModel()
    {
        $user = new User();
        $user->phone_number = 13888888888;
        $number = Sms::format($user);
        $this->assertEquals(['country_code' => 86, 'phone_number' => 13888888888], $number);
    }

    public function testformatFromString()
    {
        $number = Sms::format(13888888888);
        $this->assertEquals(['country_code' => 86, 'phone_number' => 13888888888], $number);
    }

    public function testWrongFormat()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupport format of the phone to send');
        $object = new self();
        Sms::format($object);
    }
}
