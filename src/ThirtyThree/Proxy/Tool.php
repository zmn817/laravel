<?php

namespace ThirtyThree\Proxy;

use GuzzleHttp\Client;
use InvalidArgumentException;
use GuzzleHttp\Exception\ConnectException;

class Tool
{
    public function check(...$url)
    {
        if (count($url) === 1) {
            $parse = parse_url($url[0]);
            $scheme = array_get($parse, 'scheme', 'http');
            $host = array_get($parse, 'host');
            $port = array_get($parse, 'port', $scheme === 'https' ? 443 : 80);
            $proxy = $scheme.'://'.$host.':'.$port;
        } elseif (count($url) === 3) {
            $scheme = $url[0];
            $host = $url[1];
            $port = $url[2];
            $proxy = $scheme.'://'.$host.':'.$port;
        } else {
            throw new InvalidArgumentException('Url should contain scheme,host,port.');
        }
        if (! filter_var($proxy, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Url is invalid.');
        }
        if (! in_array($scheme, ['http', 'https'])) {
            throw new InvalidArgumentException('Only support http and https scheme.');
        }

        $info = json_decode($this->requestGet('https://httpbin.org/get?show_env=1', $proxy), true);

        $type = '透明';
        if ($info['origin'] == $host) {
            $type = '高匿';
        }

        return compact('scheme', 'host', 'port', 'proxy', 'type');
    }

    protected function requestGet($url, $proxy = null)
    {
        $client = new Client();
        try {
            $response = $client->request('GET', $url, [
                'timeout' => 10,
                'proxy' => $proxy,
            ]);
        } catch (ConnectException $e) {
            throw new CheckException('Proxy unreachable', Error::PROXY_UNREACHABLE);
        }

        return (string) $response->getBody();
    }
}
