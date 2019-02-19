<?php

namespace ThirtyThree\Qiniu;

use Qiniu\Auth;
use Qiniu\Config;
use GuzzleHttp\Psr7\Request;
use App\Models\FileQiniuBucket;
use GuzzleHttp\Client as GuzzleClient;

class Client
{
    protected $bucket;
    protected $auth;

    public function __construct(FileQiniuBucket $bucket, Auth $auth)
    {
        $this->bucket = $bucket;
        $this->auth = $auth;
    }

    public function fetchAsync($body)
    {
        $config = new Config();
        $apiBase = $config->getApiHost($this->auth->getAccessKey(), $this->bucket->bucket);
        $body = json_encode($body);
        $uri = $apiBase.'/sisyphus/fetch';

        $authorization = $this->auth->authorizationV2($uri, 'POST', $body, 'application/json');
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => $authorization,
        ];

        $client = new GuzzleClient();
        $request = new Request('POST', $uri, $headers, $body);
        $response = $client->send($request);
        $json = json_decode((string) $response->getBody(), true);

        return $json;
    }
}
