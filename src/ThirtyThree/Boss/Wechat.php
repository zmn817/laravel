<?php

namespace ThirtyThree\Boss;

use GuzzleHttp\Psr7\Response;
use ThirtyThree\Request\Request;

class Wechat extends Request
{
    public function __construct($config = null)
    {
        $this->setLogger(fileLogger('boss', 'wechat-api'));
        $this->setBaseUri('https://wxapp.zhipin.com');
        $this->setConfig($config);
    }

    public function condition()
    {
        return $this->request('GET', '/bzminiapp/geek/search/condition.json');
    }

    public function jdDetail($id)
    {
        return $this->request('GET', '/bzminiapp/geek/job/detail.json?jobId='.$id);
    }

    public function company($id)
    {
        return $this->request('GET', '/bzminiapp/brand/detail.json?brandId='.$id);
    }

    public function search($search)
    {
        return $this->request('GET', '/bzminiapp/geek/search/joblist.json?'.http_build_query($search));
    }

    protected function content($method, $uri, array $content, array $options)
    {
        return [$content, [
            'session' => $this->config('session'),
            'User-Agent' => null,
        ]];
    }

    protected function response($method, $uri, array $content, array $options, Response $response)
    {
        $responseBody = (string) $response->getBody();

        $json = json_decode($responseBody, true);

        if (array_get($json, 'rescode') !== 1) {
            throw new \Exception(array_get($json, 'resmsg', 'Unknown Error'));
        }

        return array_get($json, 'data');
    }

    protected function errorMessage($responseBody)
    {
        $json = json_decode($responseBody, true);

        return array_get($json, 'resmsg', 'Unknown Error');
    }
}
