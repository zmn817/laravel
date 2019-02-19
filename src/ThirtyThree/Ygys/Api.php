<?php

namespace ThirtyThree\Ygys;

class Api
{
    protected $logger;
    protected $config;

    public function __construct()
    {
        $this->logger = fileLogger('ygys', 'api');
        $this->config = config('services.ygys');
    }

    public function analysis($content, $fileName)
    {
        $client = new \SoapClient('http://service.ygys.net/ResumeService.asmx?wsdl');

        $res = $client->TransResumeByJsonStringForFileBase64([
            'username' => $this->config['username'],
            'pwd' => $this->config['password'],
            'content' => $content,
            'ext' => '.'.attachSuffix($fileName),
        ]);

        return json_decode($res->TransResumeByJsonStringForFileBase64Result, true);
    }
}
