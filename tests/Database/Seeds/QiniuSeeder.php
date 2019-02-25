<?php

namespace ThirtyThree\Tests\Database\Seeds;

use Illuminate\Database\Seeder;
use ThirtyThree\Qiniu\Models\FileQiniuBucket;

class QiniuSeeder extends Seeder
{
    public function run()
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
    }
}
