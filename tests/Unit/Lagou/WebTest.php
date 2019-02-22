<?php

namespace ThirtyThree\Tests\Unit\Lagou;

use ThirtyThree\Lagou\Web;
use Tests\TestCase;

class WebTest extends TestCase
{
    public function testCompanyDetail()
    {
        $wechat = new Web();
        $company = $wechat->companyInfo(147);
        $this->assertArrayHasKey('baseInfo', $company);
    }
}
