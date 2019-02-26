<?php

namespace ThirtyThree\Tapd;

use GuzzleHttp\Psr7\Response;
use ThirtyThree\Request\Request;

class Tapd extends Request
{
    public function __construct($config = null)
    {
        $this->setBaseUri('https://api.tapd.cn');
        $this->setLogger(fileLogger('tapd', 'api'));
        $this->setConfig($config ?: config('services.tapd'));
    }

    public function iterations($search)
    {
        return $this->request('GET', 'iterations', $search);
    }

    public function stories($search)
    {
        return $this->request('GET', 'stories', $search);
    }

    public function storiesCount($workspace_id)
    {
        return $this->request('GET', 'stories/count', compact('workspace_id'));
    }

    public function companyProjects($company_id = null)
    {
        $company_id = $company_id ?: $this->config('company');

        return $this->request('GET', 'workspaces/projects', compact('company_id'));
    }

    protected function content($method, $uri, array $content, array $options)
    {
        return [$content, [
            'Authorization' => 'Basic '.base64_encode(sprintf('%s:%s', $this->config('username'), $this->config('password'))),
        ]];
    }

    protected function response($method, $uri, array $content, array $options, Response $response)
    {
        $responseBody = (string) $response->getBody();
        $json = json_decode($responseBody, true);

        return $json['data'];
    }

    protected function errorMessage($responseBody)
    {
        $json = json_decode($responseBody, true);

        return array_get($json, 'info', 'Unknown Error');
    }
}
