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
        $default = Qiniu::bucket();
    }

    public function testDefaultBucket()
    {
        FileQiniuBucket::create([
            'slug' => 'default',
            'name' => 'default',
            'bucket' => 'default',
            'domain' => 'http://example.com',
            'visibility' => 'private',
            'access_key' => 'xxx',
            'secret_key' => 'xxx',
        ]);
        $default = Qiniu::bucket();
        $this->assertInstanceOf(RealQiniu::class, $default);
    }
}
