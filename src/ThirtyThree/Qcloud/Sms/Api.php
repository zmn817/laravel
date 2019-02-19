<?php

namespace ThirtyThree\Qcloud\Sms;

use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use InvalidArgumentException;
use TimJuly\Exceptions\ApiException;
use GuzzleHttp\Exception\BadResponseException;

class Api
{
    protected $logger;

    protected $apiBasePath = 'https://yun.tim.qq.com/';
    protected $AppID;
    protected $AppKey;

    public function __construct()
    {
        $this->logger = fileLogger('qcloud/sms/', 'api');
        $this->AppID = config('services.qcloud_sms.app_id');
        $this->AppKey = config('services.qcloud_sms.app_key');
    }

    public function getSmsTemplate($offset = 0, $max = 50)
    {
        $params = [
            'tpl_page' => [
                'offset' => $offset,
                'max' => $max,
            ],
        ];
        // 签名
        $random = str_random(10);
        $time = time();
        $path = 'v5/tlssmssvr/get_template?'.http_build_query(['sdkappid' => $this->AppID, 'random' => $random]);
        $sig = hash('sha256',
            http_build_query(['appkey' => $this->AppKey, 'random' => $random, 'time' => $time]));
        $params['time'] = $time;
        $params['sig'] = $sig;

        return $this->send('POST', $path, $params);
    }

    public function sendByTemplate($mobile, $templateID, array $data, $sign = null)
    {
        if (is_array($mobile)) {
            if (empty($mobile['country_code']) || empty($mobile['phone_number'])) {
                throw new InvalidArgumentException('Mobile country_code and phone_number must specify');
            }
            $tel = [
                'nationcode' => $mobile['country_code'],
                'mobile' => $mobile['phone_number'],
            ];
        } else {
            $tel = [
                'nationcode' => '86',
                'mobile' => $mobile,
            ];
        }

        $params = [
            'tel' => $tel,
            'tpl_id' => $templateID,
            'params' => $data,
            'extend' => '',
        ];
        if (! empty($sign)) {
            $params['sign'] = $sign; // 短信签名
        }

        // 签名
        $random = str_random(10);
        $time = time();
        $path = 'v5/tlssmssvr/sendsms?'.http_build_query(['sdkappid' => $this->AppID, 'random' => $random]);
        $sig = hash('sha256',
            http_build_query([
                'appkey' => $this->AppKey,
                'random' => $random,
                'time' => $time,
                'mobile' => $tel['mobile'],
            ]));
        $params['time'] = $time;
        $params['sig'] = $sig;

        return $this->send('POST', $path, $params);
    }

    protected function send($method, $path, $body = [])
    {
        $uri = $this->apiBasePath.$path;

        $transferTime = null;
        $client = new Client();

        try {
            $response = $client->request($method, $uri, [
                'json' => $body,
                'on_stats' => function (TransferStats $stats) use (&$transferTime) {
                    $transferTime = $stats->getTransferTime();
                },
            ]);

            $responseBody = $response->getBody();
            $json = json_decode($responseBody, true);

            if (array_get($json, 'result') !== 0) {
                $errorMessage = array_get($json, 'errmsg', array_get($json, 'msg', '未知错误'));
                $this->logger->error($errorMessage, [
                    'method' => $method,
                    'uri' => $uri,
                    'body' => $body,
                    'response' => (string) $responseBody,
                    'transferTime' => $transferTime,
                ]);

                throw new ApiException($errorMessage, 500, $response);
            }
            $this->logger->info('call api', [
                'method' => $method,
                'uri' => $uri,
                'body' => $body,
                'transferTime' => $transferTime,
            ]);

            return $json;
        } catch (ApiException $e) {
            throw $e;
        } catch (BadResponseException $e) {
            $response = null;
            $responseBody = null;
            $errorMessage = '未知错误';

            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $responseBody = $response->getBody();
                $json = json_decode($responseBody, true);

                $errorMessage = array_get($json, 'errmsg', array_get($json, 'msg', '未知错误'));
            }

            $this->logger->error($errorMessage, [
                'method' => $method,
                'uri' => $uri,
                'body' => $body,
                'response' => (string) $responseBody,
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
