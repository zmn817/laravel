<?php

namespace ThirtyThree\Amap;

use GuzzleHttp\Psr7\Response;
use ThirtyThree\Request\Request;

class Amap extends Request
{
    public function __construct($config = null)
    {
        $this->setLogger(fileLogger('amap', 'api'));
        $this->setBaseUri('https://restapi.amap.com');
        $this->setConfig($config ?: config('services.amap'));
    }

    public function district($params = [])
    {
        return $this->request('GET', 'v3/config/district', $params);
    }

    public function geo($address)
    {
        return $this->request('GET', 'v3/geocode/geo', ['address' => $address]);
    }

    protected function content($method, $uri, array $content, array $options)
    {
        $content['key'] = $this->config['key'];

        return [$content, []];
    }

    protected function response($method, $uri, array $content, array $options, Response $response)
    {
        $responseBody = (string) $response->getBody();
        $json = json_decode($responseBody, true);

        if (array_get($json, 'status') !== '1') {
            throw new \Exception(array_get($json, 'info', 'Unknown Error'));
        }

        return $json;
    }
}
