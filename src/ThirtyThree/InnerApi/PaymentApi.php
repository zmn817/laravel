<?php

namespace ThirtyThree\InnerApi;

use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use ThirtyThree\Exceptions\ApiException;
use GuzzleHttp\Exception\BadResponseException;

class PaymentApi
{
    protected $logger;
    protected $apiBasePath;

    public function __construct()
    {
        $this->logger = fileLogger('inner-api', 'payment');
        $this->apiBasePath = rtrim(config('common.payment_url'), '/').'/';
    }

    public function thirdPartyOrder($params)
    {
        return $this->send('POST', 'payment/orders/third-party-order', $params);
    }

    public function create($params)
    {
        return $this->send('POST', 'payment/orders', $params);
    }

    protected function send($method, $path, $params = [])
    {
        $body = $params;
        $uri = $this->apiBasePath.$path;

        $transferTime = null;
        $client = new Client();

        try {
            $res = $client->request($method, $uri, [
                'json' => $body,
                'on_stats' => function (TransferStats $stats) use (&$transferTime) {
                    $transferTime = $stats->getTransferTime();
                },
            ]);

            $responseBody = $res->getBody();
            $json = json_decode($responseBody, true);

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

                $errorMessage = array_get($json, 'message', '未知错误');
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
