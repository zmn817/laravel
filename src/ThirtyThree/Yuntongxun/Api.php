<?php

namespace ThirtyThree\Yuntongxun;

use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use ThirtyThree\Exceptions\ApiException;
use GuzzleHttp\Exception\BadResponseException;

class Api
{
    protected $logger;

    protected $is_sub_account;
    protected $account_id;
    protected $account_token;
    protected $app_id;

    const SERVER_IP = 'app.cloopen.com';
    const SERVER_PORT = '8883';
    const SDK_VERSION = '2013-12-26';

    public function __construct()
    {
        $this->logger = fileLogger('yuntongxun', 'api');
        $config = config('services.yuntongxun');
        $this->is_sub_account = array_get($config, 'is_sub_account');
        $this->account_id = array_get($config, 'account_id');
        $this->account_token = array_get($config, 'account_token');
        $this->app_id = array_get($config, 'app_id');
    }

    /**
     * 设置AXB（分配X）.
     *
     * @param $aNumber (用户A的电话号码)
     * @param $bNumber (用户B的电话号码)
     * @param $config (其他配置)
     *
     * @return mixed
     */
    public function axbProvide($aNumber, $bNumber, array $config = [])
    {
        $params = [
                'aNumber' => $aNumber,
                'bNumber' => $bNumber,
            ] + $config;

        return $this->send('POST', 'nme', 'axb/'.$this->account_id.'/provide', $params);
    }

    /**
     * 双向外呼
     *
     * @param $aNumber (用户A的电话号码)
     * @param $bNumber (用户B的电话号码)
     * @param $Xnumber (指定的服务号（中间号）)
     * @param $mappingID (映射关系ID)
     *
     * @return mixed
     */
    public function axbCall($aNumber, $bNumber, $Xnumber, $mappingID)
    {
        $params = [
            'aNumber' => $aNumber,
            'bNumber' => $bNumber,
            'xNumber' => $Xnumber,
            'mappingId' => $mappingID,
        ];

        return $this->send('POST', 'nme', 'cu12/'.$this->account_id.'/makecall', $params);
    }

    /**
     * 双向回呼
     *
     * @param $aNumber (用户A的电话号码)
     * @param $bNumber (用户B的电话号码)
     * @param $config (其他配置)
     *
     * @return mixed
     */
    public function callCallback($aNumber, $bNumber, array $config = [])
    {
        $params = [
                'from' => $aNumber,
                'to' => $bNumber,
            ] + $config;

        return $this->send('POST', 'Calls', 'Callback', $params);
    }

    protected function send($method, $function, $operation, $params = [])
    {
        $params['appId'] = $this->app_id;
        $datetime = date('YmdHis');
        $endpoint = $this->buildEndpoint($datetime, $function, $operation);

        $transferTime = null;
        $client = new Client();
        try {
            $res = $client->request($method, $endpoint, [
                'body' => json_encode($params, JSON_UNESCAPED_SLASHES),
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json;charset=utf-8',
                    'Authorization' => base64_encode($this->account_id.':'.$datetime),
                ],
                'on_stats' => function (TransferStats $stats) use (&$transferTime) {
                    $transferTime = $stats->getTransferTime();
                },
            ]);
            $responseBody = $res->getBody();

            $json = json_decode($responseBody, true);
            if (array_get($json, 'statusCode') !== '000000') {
                $errorMessage = array_get($json, 'statusMsg', '未知错误');
                $this->logger->error($errorMessage, [
                    'method' => $method,
                    'uri' => $endpoint,
                    'body' => $params,
                    'response' => (string) $responseBody,
                    'transferTime' => $transferTime,
                ]);
                throw new ApiException($errorMessage, 500);
            }
            $this->logger->info('call api', [
                'method' => $method,
                'uri' => $endpoint,
                'body' => $params,
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

                $errorMessage = array_get($json, 'statusMsg', '未知错误');
            }

            $this->logger->error($errorMessage, [
                'method' => $method,
                'uri' => $endpoint,
                'body' => $params,
                'response' => (string) $responseBody,
                'transferTime' => $transferTime,
            ]);

            throw new ApiException($errorMessage, 500, $response);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(),
                [
                    'method' => $method,
                    'uri' => $endpoint,
                    'body' => $params,
                    'transferTime' => $transferTime,
                ]);
            throw new ApiException($e->getMessage(), 1);
        }
    }

    protected function buildEndpoint($datetime, $function = null, $operation = null)
    {
        $accountType = $this->is_sub_account ? 'SubAccounts' : 'Accounts';
        $sig = strtoupper(md5($this->account_id.$this->account_token.$datetime));

        $url = 'https://'.self::SERVER_IP.':'.self::SERVER_PORT.'/'.self::SDK_VERSION.'/'.$accountType.'/'.$this->account_id;
        if (! empty($function)) {
            $url .= '/'.$function;
        }
        if (! empty($operation)) {
            $url .= '/'.$operation;
        }
        $url .= '?sig='.$sig;

        return $url;
    }
}
