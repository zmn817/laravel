<?php

namespace ThirtyThree\CloudBrain;

use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use function GuzzleHttp\json_encode;
use ThirtyThree\Exceptions\ApiException;
use GuzzleHttp\Exception\BadResponseException;

class Api
{
    protected $logger;
    protected $apiBasePath;
    protected $config;

    public function __construct()
    {
        $this->logger = fileLogger('cloudbrain', 'api');
        $this->config = config('services.cloudbrain');
        $this->apiBasePath = array_get($this->config, 'api_url');
    }

    public function question($content)
    {
        return $this->send('POST', '/api/test/nextq', json_encode($content));
    }

    public function questionSave($question)
    {
        $options = [];
        $sort = 1;
        foreach ($question->options as $option) {
            $options[] = [
                'id' => (string) $option->id,
                'text' => (string) $option->option,
                'sort' => $sort++,
                'correct' => (bool) $option->correct,
            ];
        }
        $content = [
            'text' => $question->stem,
            'score' => 0,
            'difficulty' => $question->level,
            'content' => [
                'type' => 'options',
                'text' => $question->stem,
                'candidates' => [
                    'options' => $options,
                ],
            ],
        ];

        return $this->send('POST', '/api/data/quest/'.$question->id, json_encode($content));
    }

    public function questionSaveSuite($questionID, $suites)
    {
        return $this->send('POST', '/api/data/quest/'.$questionID.'/suites', json_encode($suites));
    }

    public function questionSaveKpoint($questionID, $kpoints)
    {
        return $this->send('POST', '/api/data/quest/'.$questionID.'/kpoints', json_encode($kpoints));
    }

    public function questionDelete($questionID)
    {
        return $this->send('DELETE', '/api/data/quest/'.$questionID);
    }

    public function questionDeleteSuite($questionID)
    {
        return $this->send('DELETE', '/api/data/quest/'.$questionID.'/suites');
    }

    public function questionDeleteKpoint($questionID)
    {
        return $this->send('DELETE', '/api/data/quest/'.$questionID.'/kpoints');
    }

    public function evaluationSync($id, $content)
    {
        return $this->send('POST', '/api/data/test/'.$id, json_encode($content));
    }

    public function jdSave($id, $content)
    {
        return $this->send('POST', '/api/data/job/'.$id, json_encode($content));
    }

    public function jdStatus($id, $status)
    {
        return $this->send('POST', '/api/data/job/'.$id.'/status', json_encode(['effective' => $status]));
    }

    protected function send($method, $path, $body = null)
    {
        $uri = $this->apiBasePath.$path;

        $transferTime = null;
        $client = new Client();

        $headers = [
            'Content-Type' => 'application/json; charset=utf-8',
            'Date' => now()->toRfc7231String(),
        ];
        $headers = $this->signature($method, $path, $body, $headers);
        try {
            $res = $client->request($method, $uri, [
                'headers' => $headers,
                'body' => $body,
                'on_stats' => function (TransferStats $stats) use (&$transferTime) {
                    $transferTime = $stats->getTransferTime();
                },
            ]);

            $responseBody = (string) $res->getBody();
            $json = json_decode($responseBody, true);

            /*$this->logger->info('call api', [
                'method' => $method,
                'uri' => $uri,
                'body' => $body,
                'response' => (string) $responseBody,
                'transferTime' => $transferTime,
            ]);*/

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

    public function signature($method, $path, $body, $headers)
    {
        $date = $headers['Date'];
        $contentType = $headers['Content-Type'];
        $canonicalizedHeaders = null;
        $contentMD5 = md5($body);
        $stringToSign = sprintf("%s\n%s\n%s\n%s\n%s\n", $method, $path, $date, $contentType, $contentMD5);
        if (! empty($canonicalizedHeaders)) {
            $stringToSign .= $canonicalizedHeaders."\n";
        }
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->config['api_secret'], true));
        $authorization = sprintf('CBS %s:%s', $this->config['api_key'], $signature);
        $headers['Authorization'] = $authorization;
        $headers['Content-MD5'] = $contentMD5;

        return $headers;
    }
}
