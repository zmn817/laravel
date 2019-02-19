<?php

namespace ThirtyThree\Dingtalk;

use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use TimJuly\Exceptions\ApiException;
use GuzzleHttp\Exception\BadResponseException;

class Api
{
    protected $logger;
    protected $apiBasePath;

    public function __construct()
    {
        $this->logger = fileLogger('dingtalk', 'api');
        $this->apiBasePath = 'https://oapi.dingtalk.com/';
    }

    public function token($id, $secret)
    {
        $params = [
            'corpid' => $id,
            'corpsecret' => $secret,
        ];

        return $this->send('GET', 'gettoken', $params);
    }

    public function snsToken($id, $secret)
    {
        $params = [
            'appid' => $id,
            'appsecret' => $secret,
        ];

        return $this->send('GET', 'sns/gettoken', $params);
    }

    public function getUseridByUnionid($unionid)
    {
        $params = [
            'access_token' => AccessToken::token(),
            'unionid' => $unionid,
        ];

        return $this->send('GET', 'user/getUseridByUnionid', $params);
    }

    protected function send($method, $path, $body = [])
    {
        $uri = $this->apiBasePath.$path;

        $transferTime = null;
        $client = new Client();

        $r = $method == 'GET' ? 'query' : 'form_params';
        try {
            $res = $client->request($method, $uri, [
                $r => $body,
                'on_stats' => function (TransferStats $stats) use (&$transferTime) {
                    $transferTime = $stats->getTransferTime();
                },
            ]);

            $responseBody = $res->getBody();
            $json = json_decode($responseBody, true);

            if (array_get($json, 'errcode') !== 0) {
                $errorMessage = array_get($json, 'errmsg', '未知错误');
                $this->logger->error($errorMessage, [
                    'method' => $method,
                    'uri' => $uri,
                    'body' => $body,
                    'response' => (string) $responseBody,
                    'transferTime' => $transferTime,
                ]);

                throw new ApiException($errorMessage, 500, $json);
            }

            $this->logger->info('call api', [
                'method' => $method,
                'uri' => $uri,
                'body' => $body,
                'transferTime' => $transferTime,
            ]);

            return $json;
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $responseBody = null;
            if (! empty($response)) {
                $responseBody = $response->getBody();
            }
            $json = json_decode($responseBody, true);

            $errorMessage = array_get($json, 'errmsg', '未知错误');
            $this->logger->error($errorMessage, [
                'method' => $method,
                'uri' => $uri,
                'body' => $body,
                'response' => (string) $responseBody,
                'transferTime' => $transferTime,
            ]);

            throw new ApiException($errorMessage, 500, $json);
        } catch (ApiException $e) {
            throw $e;
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
