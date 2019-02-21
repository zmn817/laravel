<?php

namespace ThirtyThree\Qcloud\Aai;

use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use ThirtyThree\Exceptions\RequestException;
use GuzzleHttp\Exception\BadResponseException;

class Api
{
    protected $logger;

    protected $apiBasePath = 'https://aai.qcloud.com/';
    protected $appID;
    protected $secretID;
    protected $secretKey;

    public function __construct()
    {
        $this->logger = fileLogger('qcloud/aai/', 'api');
        $this->appID = config('qcloud.aai.app_id');
        $this->secretID = config('qcloud.aai.secret_id');
        $this->secretKey = config('qcloud.aai.secret_key');
    }

    public function asr($url)
    {
        $params = [
            'sub_service_type' => 0,
            'engine_model_type' => '8k_0',
            'callback_url' => apiRoute('qcloud.aai.callback'),
            'res_text_format' => 0,
            'source_type' => 0,
            'res_type' => 1,
            'url' => $url,
            'timestamp' => time(),
            'expired' => time() + 86400,
            'nonce' => rand(),
        ];

        return $this->send('POST', 'asr/v1', $params);
    }

    protected function send($method, $path, $params = [], $body = null)
    {
        $uri = $this->apiBasePath.$path.'/'.$this->appID;
        $signature = $this->signature($method, $uri, $params);
        $uri .= '?'.http_build_query($params);

        $transferTime = null;
        $client = new Client();

        try {
            $response = $client->request($method, $uri, [
                'headers' => [
                    'Authorization' => $signature,
                ],
                'body' => $body,
                'on_stats' => function (TransferStats $stats) use (&$transferTime) {
                    $transferTime = $stats->getTransferTime();
                },
            ]);

            $responseBody = $response->getBody();
            $json = json_decode($responseBody, true);

            if (array_get($json, 'code') !== 0) {
                $errorMessage = array_get($json, 'message', '未知错误');
                $this->logger->error($errorMessage, [
                    'method' => $method,
                    'uri' => $uri,
                    'body' => $body,
                    'response' => (string) $responseBody,
                    'transferTime' => $transferTime,
                ]);

                throw new RequestException($errorMessage, 500, $response);
            }
            $this->logger->info('call api', [
                'method' => $method,
                'uri' => $uri,
                'body' => $body,
                'transferTime' => $transferTime,
            ]);

            return $json;
        } catch (RequestException $e) {
            throw $e;
        } catch (BadResponseException $e) {
            $response = null;
            $responseBody = null;
            $errorMessage = '未知错误';

            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $responseBody = $response->getBody();
                $json = json_decode($responseBody, true);

                $errorMessage = array_get($json, 'message', '未知错误');
            }

            $this->logger->error($errorMessage, [
                'method' => $method,
                'uri' => $uri,
                'body' => $body,
                'response' => (string) $responseBody,
                'transferTime' => $transferTime,
            ]);

            throw new RequestException($errorMessage, 500, $response);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(),
                [
                    'method' => $method,
                    'uri' => $uri,
                    'body' => $body,
                    'transferTime' => $transferTime,
                ]
            );

            throw new RequestException($e->getMessage());
        }
    }

    public function signature($method, $uri, &$params)
    {
        $uriInfo = parse_url($uri);
        $params['secretid'] = $this->secretID;
        ksort($params);
        $param_str = '';
        foreach ($params as $k => $v) {
            $param_str = $param_str."$k=$v&";
        }
        $param_str = rtrim($param_str, '&');

        $sig_str = strtoupper($method).$uriInfo['host'].$uriInfo['path'].'?'.$param_str;

        return base64_encode(hash_hmac('sha1', $sig_str, $this->secretKey, true));
    }
}
