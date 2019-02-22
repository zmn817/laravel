<?php

namespace ThirtyThree\Lagou;

use RuntimeException;
use ThirtyThree\Request\Request;

class Web extends Request
{
    public function __construct($config = null)
    {
        $this->setLogger(fileLogger('lagou', 'web-api'));
        $this->setBaseUri('https://www.lagou.com');
        $this->setConfig($config);
    }

    public function companyInfo($id)
    {
        $html = $this->request('GET', '/gongsi/'.$id.'.html');
        preg_match('/<script id="companyInfoData" type="text\/html">(.*)<\/script>/', $html, $matches);
        if (empty($matches[1])) {
            throw new RuntimeException('解析公司信息失败');
        }

        return json_decode($matches[1], true);
    }

    protected function content($method, $uri, array $content, array $options)
    {
        return [$content, [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS 13_0_0) AppleWebKit/600.0 (KHTML, like Gecko) Chrome/70.0.0.0 Safari/600.0',
            'Cookie' => $this->config('cookie'),
        ]];
    }
}
