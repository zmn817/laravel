<?php

namespace ThirtyThree\Request;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\TransferStats;
use ThirtyThree\Exceptions\ApiException;
use GuzzleHttp\Exception\BadResponseException;

class Request
{
    protected $logger;
    protected $apiBasePath;

    protected function request($method, $uri = '', array $content = [], array $options = [])
    {
        $transferTime = null;
        $client = new Client([
            'base_uri' => $this->apiBasePath,
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

            return $this->response($method, $uri, $content, $options, $response, compact('transferTime'));
        } catch (ApiException $e) {
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

            $this->logger->error($errorMessage, [
                'method' => $method,
                'uri' => $uri,
                'content' => $content,
                'options' => $options,
                'transferTime' => $transferTime,
                'response' => (string) $responseBody,
                'responseStatus' => $responseStatus,
            ]);

            throw new ApiException($errorMessage, 500, $response);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), [
                'method' => $method,
                'uri' => $uri,
                'content' => $content,
                'options' => $options,
                'transferTime' => $transferTime,
            ]);

            throw new ApiException($e->getMessage());
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

    protected function response($method, $uri, array $content, array $options, Response $response, array $extra = [])
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
