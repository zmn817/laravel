<?php

namespace ThirtyThree\Amap;

use GuzzleHttp\Psr7\Response;
use ThirtyThree\Request\Request;
use ThirtyThree\Exceptions\ApiException;

class Amap extends Request
{
    protected $logger;
    protected $config;
    protected $apiBasePath;

    public function __construct($config = null)
    {
        $this->logger = fileLogger('amap', 'api');
        $this->apiBasePath = 'https://restapi.amap.com';
        $this->config = $config ?: config('services.amap');
    }

    public function district($params)
    {
        return $this->request('GET', '/v3/config/district', $params);
    }

    public function geo($address)
    {
        return $this->request('GET', '/v3/geocode/geo', ['address' => $address]);
    }

    protected function content($method, $uri, array $content, array $options)
    {
        $content['key'] = $this->config['key'];

        return [$content, []];
    }

    protected function response($method, $uri, array $content, array $options, Response $response, array $extra = [])
    {
        $responseBody = (string) $response->getBody();
        $json = json_decode($responseBody, true);

        if (array_get($json, 'status') !== '1') {
            $errorMessage = array_get($json, 'info', 'Unknown Error');
            $this->logger->error($errorMessage, [
                'method' => $method,
                'uri' => $uri,
                'content' => $content,
                'options' => $options,
                'transferTime' => array_get($extra, 'transferTime'),
                'response' => (string) $responseBody,
            ]);

            throw new ApiException($errorMessage, 500, $json);
        }

        return $json;
    }
}
