<?php

namespace ThirtyThree\Resumesdk;

use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use ThirtyThree\Exceptions\ApiException;
use GuzzleHttp\Exception\BadResponseException;

class Api
{
    protected $logger;
    protected $apiBasePath;
    protected $config;

    public function __construct()
    {
        $this->logger = fileLogger('resumesdk', 'api');
        $this->apiBasePath = 'http://www.resumesdk.com';
        $this->config = config('services.resumesdk');
    }

    public function analysis($content, $fileName)
    {
        return $this->send('POST', '/api/parse', [
            'base_cont' => base64_encode($content),
            'fname' => $fileName,
        ]);
    }

    protected function send($method, $path, $body)
    {
        $uri = $this->apiBasePath.$path;

        $transferTime = null;
        $client = new Client();
        $body['uid'] = (int) $this->config['api_key'];
        $body['pwd'] = $this->config['api_secret'];

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic '.base64_encode('admin:2015'),
            'User-Agent' => null,
        ];

        try {
            $res = $client->request($method, $uri, [
                'headers' => $headers,
                'body' => json_encode($body, JSON_UNESCAPED_UNICODE),
                'on_stats' => function (TransferStats $stats) use (&$transferTime) {
                    $transferTime = $stats->getTransferTime();
                },
                'user_agent' => false,
            ]);

            $responseBody = (string) $res->getBody();
            $json = json_decode($responseBody, true);

            return $json;
        } catch (ApiException $e) {
            throw $e;
        } catch (BadResponseException $e) {
            $response = null;
            $responseStatus = null;
            $responseBody = null;
            $errorMessage = '未知错误';

            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $responseStatus = $response->getStatusCode();
                $responseBody = $response->getBody();
                $json = json_decode($responseBody, true);

                $errorMessage = array_get($json, 'error', '未知错误');
            }

            $this->logger->error($errorMessage, [
                'method' => $method,
                'uri' => $uri,
                'body' => $body,
                'response' => (string) $responseBody,
                'response_status' => $responseStatus,
                'transferTime' => $transferTime,
            ]);

            throw new ApiException($errorMessage, 500, $response);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(),
                [
                    'method' => $method,
                    'uri' => $uri,
                    'body' => $body,
                    'transferTime' => $transferTime,
                ]
            );

            throw new ApiException($e->getMessage());
        }
    }
}
