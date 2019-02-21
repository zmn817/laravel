<?php

namespace ThirtyThree\Qcloud\Cloud;

use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use ThirtyThree\Exceptions\RequestException;
use GuzzleHttp\Exception\BadResponseException;

class Api
{
    protected $logger;

    protected $apiBasePath;
    protected $secretID;
    protected $secretKey;
    protected $region = 'bj';
    protected $method = 'POST';

    public function __construct($name)
    {
        $models = [
            'sec' => 'csec.api.qcloud.com',
        ];
        if (! empty($models[$name])) {
            $this->apiBasePath = 'https://'.$models[$name].'/v2/index.php';
        } else {
            $this->apiBasePath = 'https://'.$name.'.api.qcloud.com/v2/index.php';
        }

        $this->logger = fileLogger('qcloud/cloud/'.$name, 'api');

        $this->secretID = config('qcloud.cloud.secret_id');
        $this->secretKey = config('qcloud.cloud.secret_key');
        $region = config('qcloud.cloud.region');
        if (! empty($region)) {
            $this->region = $region;
        }
    }

    public function account($secretID, $secretKey)
    {
        $this->secretID = $secretID;
        $this->secretKey = $secretKey;

        return $this;
    }

    public function region($region)
    {
        $this->region = $region;

        return $this;
    }

    public function method($method)
    {
        $this->method = $method;

        return $this;
    }

    public function __call($name, $arguments)
    {
        return $this->send($name, array_get($arguments, 0, []));
    }

    protected function send($action, $body = [])
    {
        $uri = $this->apiBasePath;
        $method = $this->method;

        $body['Action'] = $action;
        $signature = $this->signature($method, $uri, $body);
        $body['Signature'] = $signature;

        $transferTime = null;
        $client = new Client();
        $r = $method == 'GET' ? 'query' : 'form_params';
        try {
            $response = $client->request($method, $uri, [
                'headers' => [
                    'Authorization' => $signature,
                ],
                $r => $body,
                'on_stats' => function (TransferStats $stats) use (&$transferTime) {
                    $transferTime = $stats->getTransferTime();
                },
            ]);

            $responseBody = $response->getBody();
            $json = json_decode($responseBody, true);

            if (! empty($json['code'])) {
                $errorMessage = array_get($json, 'message', '未知错误');
                $this->logger->error($errorMessage, [
                    'method' => $method,
                    'uri' => $uri,
                    'body' => $body,
                    'response' => (string) $responseBody,
                    'transferTime' => $transferTime,
                ]);

                throw new RequestException($errorMessage, 500, $response);
            } elseif (! empty($json['Response']['Error'])) {
                $errorMessage = array_get($json, 'Response.Error.Message', '未知错误');
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
        $params['Nonce'] = rand(1, 65535);
        $params['Region'] = $this->region;
        $params['SecretId'] = $this->secretID;
        $params['SignatureMethod'] = 'HmacSHA1';
        $params['Timestamp'] = time();

        $uriInfo = parse_url($uri);
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
