<?php

namespace ThirtyThree\Tests\Unit\Boss;

use ThirtyThree\Boss\Wechat;
use ThirtyThree\Tests\TestCase;
use ThirtyThree\Exceptions\RequestException;

class WechatTest extends TestCase
{
    public function testWechatErrorSession()
    {
        $this->expectException(RequestException::class);
        $wechat = new Wechat('xxxxxx');
        $wechat->search([
            'city' => 'xx',
            'query' => 'xx',
            'page' => 1,
        ]);
    }
}
