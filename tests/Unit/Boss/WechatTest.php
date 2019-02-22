<?php

namespace ThirtyThree\Tests\Unit\Boss;

use Illuminate\Support\Str;
use ThirtyThree\Boss\Wechat;
use Tests\TestCase;
use ThirtyThree\Exceptions\RequestException;

class WechatTest extends TestCase
{
    public function testGetCondition()
    {
        $session = Str::random();
        $wechat = new Wechat(['session' => $session]);
        $condition = $wechat->condition();
        $this->assertNotEmpty($condition);
    }

    public function testSearchJob()
    {
        $this->expectException(RequestException::class);

        $session = Str::random();
        $wechat = new Wechat(['session' => $session]);
        $wechat->search([
            'city' => '北京',
            'query' => 'PHP',
            'page' => 1,
        ]);
    }
}
