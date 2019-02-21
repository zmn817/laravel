<?php

namespace ThirtyThree\Tests\Unit\Reqeust;

use ThirtyThree\Amap\Amap;
use ThirtyThree\Tests\TestCase;
use ThirtyThree\Exceptions\RequestException;

class AmapTest extends TestCase
{
    public function testEmptyConfig()
    {
        $this->expectException(RequestException::class);

        $amap = new Amap();
        $amap->setConfig(null);
        $amap->district([]);
    }

    public function testDistrict()
    {
        $amap = new Amap();
        $districts = $amap->district();
        $this->assertArrayHasKey('districts', $districts);
    }

    public function testSubDistrict()
    {
        $amap = new Amap();
        $parent = $amap->district();
        $code = $parent['districts'][0]['districts'][0]['adcode'];
        $name = $parent['districts'][0]['districts'][0]['name'];
        $districts = $amap->district(['keywords' => $code, 'filter' => $code]);
        $this->assertEquals($name, $districts['districts'][0]['name']);
    }

    public function testGeo()
    {
        $amap = new Amap();
        $info = $amap->geo('北京天安门');
        $this->assertArrayHasKey('geocodes', $info);
    }
}
