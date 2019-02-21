<?php

namespace ThirtyThree\Tests\Unit\Lagou;

use ThirtyThree\Lagou\Wechat;
use ThirtyThree\Tests\TestCase;

class WechatTest extends TestCase
{
    public function testSearchJob()
    {
        $wechat = new Wechat();
        $jobs = $wechat->search([
            'city' => '北京',
            'keyword' => 'PHP',
            'sort' => 'TIME',
            'pageNo' => 1,
            'pageSize' => 10,
        ]);
        var_dump($jobs);
        $this->assertNotEmpty($jobs);
    }

    public function testJdDetail()
    {
        $wechat = new Wechat();
        $jd = $wechat->jdDetail(1);
        $this->assertEquals(1, $jd['id']);
    }

    public function testCompanyDetail()
    {
        $wechat = new Wechat();
        $company = $wechat->company(3);
        $this->assertEquals(3, $company['id']);
    }
}
