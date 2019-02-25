<?php

namespace ThirtyThree\Qiniu;

use Qiniu as Q;
use RuntimeException;
use ThirtyThree\Qiniu\Models\FileQiniuBucket;

class QiniuManager
{
    protected $app;

    protected $buckets = [];
    protected $defaultBucket;

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Url转码.
     *
     * @param $uri
     *
     * @return string
     */
    public function uriEncode($uri)
    {
        return Q\base64_urlSafeEncode($uri);
    }

    /**
     * Fop 指令状态查询.
     *
     * @param $id
     *
     * @return array
     */
    public function PFopStatus($id)
    {
        $client = new Client();
        $response = $client->get('https://api.qiniu.com/status/get/prefop?id='.$id);
        $json = json_decode($response->getBody(), true);

        return $json;
    }

    public function bucket($slug = null)
    {
        $slug = $slug ?: $this->getDefaultBucket();

        return $this->buckets[$slug] = $this->get($slug);
    }

    protected function getDefaultBucket()
    {
        if (! is_null($this->defaultBucket)) {
            return $this->defaultBucket;
        }
        $bucket = FileQiniuBucket::first();

        if (empty($bucket)) {
            throw new RuntimeException("Qiniu [{$bucket}] not configured");
        }
        $this->defaultBucket = $bucket->slug;

        return $bucket->slug;
    }

    protected function get($slug)
    {
        return $this->buckets[$slug] ?? $this->resolve($slug);
    }

    protected function resolve($slug)
    {
        return new Qiniu($slug);
    }

    public function __call($method, $parameters)
    {
        return $this->bucket()->$method(...$parameters);
    }
}
