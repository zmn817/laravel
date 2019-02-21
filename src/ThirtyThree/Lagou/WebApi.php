<?php

namespace ThirtyThree\Lagou;

use RuntimeException;
use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use ThirtyThree\Exceptions\RequestException;
use GuzzleHttp\Exception\BadResponseException;

class WebApi
{
    protected $logger;
    protected $apiBasePath;
    protected $config;
    protected $cookie;

    public function __construct($cookie)
    {
        $this->logger = fileLogger('lagou', 'wechat-api');
        $this->apiBasePath = 'https://www.lagou.com';
        $this->cookie = $cookie;
    }

    public function companyInfo($id)
    {
        $html = $this->send('GET', '/gongsi/'.$id.'.html');
        preg_match('/<script id="companyInfoData" type="text\/html">(.*)<\/script>/', $html, $matches);
        if (empty($matches[1])) {
            throw new RuntimeException('解析公司信息失败');
        }

        return json_decode($matches[1], true);
    }

    protected function send($method, $path, $body = null)
    {
        $uri = $this->apiBasePath.$path;

        $transferTime = null;
        $client = new Client();

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS 12_0_0) AppleWebKit/600.0 (KHTML, like Gecko) Chrome/70.0.0.0 Safari/600.0',
            'Cookie' => $this->cookie,
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

            return $responseBody;
        } catch (RequestException $e) {
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
}
