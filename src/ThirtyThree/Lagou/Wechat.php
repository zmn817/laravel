<?php

namespace ThirtyThree\Lagou;

use GuzzleHttp\Psr7\Response;
use ThirtyThree\Request\Request;

class Wechat extends Request
{
    public function __construct($config = null)
    {
        $this->setLogger(fileLogger('lagou', 'wechat-api'));
        $this->setBaseUri('https://weapp.lagou.com');
    }

    public function jdDetail($id)
    {
        return $this->request('GET', '/api/job/'.$id);
    }

    public function company($id)
    {
        return $this->request('GET', '/api/company/'.$id);
    }

    public function search($search)
    {
        return $this->request('POST', '/api/job/search', $search);
    }

    protected function content($method, $uri, array $content, array $options)
    {
        return [$content, [
            'Referer' => 'https://servicewechat.com/wx7523c9b73699af04/188/page-frame.html',
            'User-Agent' => null,
        ]];
    }

    protected function response($method, $uri, array $content, array $options, Response $response)
    {
        $responseBody = (string) $response->getBody();

        $json = json_decode($responseBody, true);

        if (array_get($json, 'success') !== true) {
            throw new \Exception(array_get($json, 'error', 'Unknown Error'));
        }

        return array_get($json, 'data');
    }
}
