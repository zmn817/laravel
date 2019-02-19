<?php

namespace ThirtyThree\Lagou;

use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use ThirtyThree\Exceptions\ApiException;
use GuzzleHttp\Exception\BadResponseException;

class WechatApi
{
    protected $logger;
    protected $apiBasePath;
    protected $config;

    public function __construct()
    {
        $this->logger = fileLogger('lagou', 'wechat-api');
        $this->apiBasePath = 'https://weapp.lagou.com';
    }

    public function jdDetail($id)
    {
        return $this->send('GET', '/api/job/'.$id);
    }

    public function company($id)
    {
        return $this->send('GET', '/api/company/'.$id);
    }

    public function search($search)
    {
        return $this->send('POST', '/api/job/search', $search);
    }

    protected function send($method, $path, $body = null)
    {
        $uri = $this->apiBasePath.$path;

        $transferTime = null;
        $client = new Client();

        $headers = [
            'Referer' => 'https://servicewechat.com/wx7523c9b73699af04/100/page-frame.html',
            'User-Agent' => null,
        ];
        try {
            $res = $client->request($method, $uri, [
                'headers' => $headers,
                'json' => $body,
                'on_stats' => function (TransferStats $stats) use (&$transferTime) {
                    $transferTime = $stats->getTransferTime();
                },
            ]);

            $responseBody = (string) $res->getBody();
            $responseStatus = $res->getStatusCode();
            $json = json_decode($responseBody, true);

            if (array_get($json, 'success') !== true) {
                $this->logger->error('获取数据错误', [
                    'method' => $method,
                    'uri' => $uri,
                    'body' => $body,
                    'response' => (string) $responseBody,
                    'response_status' => $responseStatus,
                    'transferTime' => $transferTime,
                ]);

                throw new ApiException('获取数据错误');
            }

            return array_get($json, 'data');
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
