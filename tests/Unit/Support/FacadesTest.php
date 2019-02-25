<?php

namespace ThirtyThree\Tests\Unit\Support;

use Qiniu;
use Tests\TestCase;
use ThirtyThree\Qiniu\Qiniu as RealQiniu;

class FacadesTest extends TestCase
{
    public function testQiniu()
    {
        $default = Qiniu::bucket();
        $this->assertInstanceOf(RealQiniu::class, $default);
    }
}
