<?php

namespace ThirtyThree\Geetest;

use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use ThirtyThree\Exceptions\RequestException;
use GuzzleHttp\Exception\BadResponseException;

class Api
{
    protected $logger;
    protected $apiBasePath;

    public function __construct()
    {
        $this->logger = fileLogger('geetest', 'api');
        $this->apiBasePath = 'http://api.geetest.com/';
    }

    public function challenge($captcha_id, $extra = [])
    {
        $params = [
            'gt' => $captcha_id,
            'new_captcha' => 1,
        ];
        $params = array_merge($params, $extra);

        return $this->send('POST', 'register.php', $params);
    }

    public function verify($captcha_id, $challenge, $seccode, $extra = [])
    {
        $params = [
            'captchaid' => $captcha_id,
            'challenge' => $challenge,
            'seccode' => $seccode,
            'timestamp' => time(),
            'json_format' => 1,
            'sdk' => 'PHP_3.0.0',
        ];
        $params = array_merge($params, $extra);

        return $this->send('POST', 'validate.php', $params);
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

            $responseBody = (string) $res->getBody();

            /*$this->logger->info('call api', [
                'method' => $method,
                'uri' => $uri,
                'body' => $body,
                'response' => (string) $responseBody,
                'transferTime' => $transferTime,
            ]);*/

            return $responseBody;
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

                $errorMessage = array_get($json, 'error', '未知错误');
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
}
