<?php

namespace ThirtyThree\Tests\Unit\Qiniu;

use Qiniu;
use Tests\TestCase;
use RuntimeException;
use ThirtyThree\Qiniu\Qiniu as RealQiniu;
use ThirtyThree\Qiniu\Models\FileQiniuBucket;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class QiniuTest extends TestCase
{
    use DatabaseTransactions;

    public function testEmptyQiniuBucket()
    {
        $this->expectException(RuntimeException::class);
        Qiniu::bucket();
    }

    public function testDefaultBucket()
    {
        FileQiniuBucket::create([
            'slug' => 'default',
            'name' => 'default',
            'bucket' => 'default',
            'domain' => 'http://example.com',
            'visibility' => 'public',
            'access_key' => 'xxx',
            'secret_key' => 'xxx',
        ]);
        $default = Qiniu::bucket();
        $this->assertInstanceOf(RealQiniu::class, $default);

        $default2 = Qiniu::bucket();
        $url = $default2->url('test.txt');
        $this->assertEquals('http://example.com/test.txt', $url);
    }

    public function testUriEncode()
    {
        $this->assertEquals('c3ViamVjdHM_X2Q9MQ==', Qiniu::uriEncode('subjects?_d=1'));
    }

    public function testPFopStatus()
    {
        $this->expectException(\GuzzleHttp\Exception\RequestException::class);
        $res = Qiniu::PFopStatus('mock_id');
    }
}
