<?php

namespace ThirtyThree\Request;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\TransferStats;
use ThirtyThree\Exceptions\RequestException;
use GuzzleHttp\Exception\BadResponseException;

class Request
{
    protected $logger;
    protected $baseUri;
    protected $config;
    protected $shouldLogWhenSuccess = false;

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function logger()
    {
        return $this->logger ?: fileLogger('request', 'common');
    }

    public function setBaseUri($uri)
    {
        $this->baseUri = $uri;
    }

    public function baseUri()
    {
        return $this->baseUri;
    }

    public function setConfig(array $config = null)
    {
        $this->config = $config;
    }

    public function config($key = null)
    {
        return array_get($this->config, $key);
    }

    public function shouldLogWhenSuccess()
    {
        return $this->shouldLogWhenSuccess;
    }

    public function logWhenSuccess()
    {
        $this->shouldLogWhenSuccess = true;
    }

    public function doNotLogWhenSuccess()
    {
        $this->shouldLogWhenSuccess = false;
    }

    public function request($method, $uri = '', array $content = [], array $options = [])
    {
        $transferTime = null;
        $client = new Client([
            'base_uri' => $this->baseUri(),
        ]);

        $contentType = $this->contentType($method, $uri, $content, $options);
        list($content, $headers) = $this->content($method, $uri, $content, $options);
        $headers['X-Request-Id'] = app('context')->id(); // 上下文 ID
        try {
            $response = $client->request($method, $uri, [
                'headers' => $headers,
                $contentType => $content,
                'on_stats' => function (TransferStats $stats) use (&$transferTime) {
                    $transferTime = $stats->getTransferTime();
                },
            ]);
            $responseBody = (string) $response->getBody();

            if ($this->shouldLogWhenSuccess()) {
                $this->logger()->info('request success', [
                    'method' => $method,
                    'base_uri' => $this->baseUri(),
                    'uri' => $uri,
                    'content' => $content,
                    'options' => $options,
                    'transferTime' => $transferTime,
                    'response' => $responseBody,
                    'responseStatus' => $response->getStatusCode(),
                ]);
            }

            try {
                return $this->response($method, $uri, $content, $options, $response);
            } catch (\Throwable $th) {
                $errorMessage = $th->getMessage();
                $this->logger->error($errorMessage, [
                    'method' => $method,
                    'base_uri' => $this->baseUri(),
                    'uri' => $uri,
                    'content' => $content,
                    'options' => $options,
                    'transferTime' => $transferTime,
                    'response' => (string) $responseBody,
                    'responseStatus' => $response->getStatusCode(),
                ]);

                throw new RequestException($errorMessage, 500, $response);
            }
        } catch (RequestException $e) {
            throw $e;
        } catch (BadResponseException $e) {
            $response = null;
            $responseStatus = null;
            $responseBody = null;
            $errorMessage = 'Unknown Error';

            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $responseStatus = $response->getStatusCode();
                $responseBody = (string) $response->getBody();

                $errorMessage = $this->errorMessage($responseBody);
            }

            $this->logger()->error($errorMessage, [
                'method' => $method,
                'base_uri' => $this->baseUri(),
                'uri' => $uri,
                'content' => $content,
                'options' => $options,
                'transferTime' => $transferTime,
                'response' => $responseBody,
                'responseStatus' => $responseStatus,
            ]);

            throw new RequestException($errorMessage, 500, $response);
        } catch (\Exception $e) {
            $this->logger()->error($e->getMessage(), [
                'method' => $method,
                'base_uri' => $this->baseUri(),
                'uri' => $uri,
                'content' => $content,
                'options' => $options,
                'transferTime' => $transferTime,
            ]);

            throw new RequestException($e->getMessage());
        }
    }

    protected function contentType($method, $uri, array $content, array $options)
    {
        return $method == 'GET' ? 'query' : 'form_params';
    }

    protected function content($method, $uri, array $content, array $options)
    {
        return [$content, []];
    }

    protected function response($method, $uri, array $content, array $options, Response $response)
    {
        $responseBody = (string) $response->getBody();

        return $responseBody;
    }

    protected function errorMessage($responseBody)
    {
        $json = json_decode($responseBody, true);

        return array_get($json, 'error', 'Unknown Error');
    }
}
