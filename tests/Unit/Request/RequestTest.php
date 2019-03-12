<?php

namespace ThirtyThree\Tests\Unit\Reqeust;

use Mockery;
use Monolog\Logger;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use GuzzleHttp\Psr7\Response;
use ThirtyThree\Tests\TestCase;
use ThirtyThree\Request\Request;
use ThirtyThree\Exceptions\RequestException;

class RequestTest extends TestCase
{
    public function testDefautLogger()
    {
        $request = new Request();
        $logger = $request->logger();
        $this->assertInstanceOf(Logger::class, $logger);
    }

    public function testSetLogger()
    {
        $request = new Request();
        $logger = fileLogger('test', 'a');
        $request->setLogger($logger);
        $this->assertSame($request->logger(), $logger);
    }

    public function testSetBaseUri()
    {
        $request = new Request();
        $request->setBaseUri('https://example.com');
        $this->assertEquals('https://example.com', $request->baseUri());
    }

    public function testSetConfig()
    {
        $request = new Request();
        $request->setConfig([1, 2, 3]);
        $this->assertEquals([1, 2, 3], $request->config());
    }

    public function testLogRequestWhenSuccess()
    {
        $request = new Request();
        $client = Mockery::mock(Client::class);
        $client->allows()->request('GET', 'https://example.com', Mockery::type('array'))->andReturn(new Response(200, [], 'mock-response'));
        $request->setClient($client);

        $request->logWhenSuccess();

        $path = Str::random();
        $name = Str::random();
        $logger = fileLogger($path, $name);
        $request->setLogger($logger);
        $path = 'logs/'.trim($path).'/';
        $name = $name.'-'.date('Y-m-d');

        $request->request('GET', 'https://example.com');
        $content = file_get_contents(storage_path($path.$name.'.log'));
        $this->assertNotEmpty($content);

        $request->doNotLogWhenSuccess();
        $request->request('GET', 'https://example.com');
        $same = file_get_contents(storage_path($path.$name.'.log'));
        $this->assertEquals($content, $same);
    }

    public function testRequestException()
    {
        $exception = null;
        $request = new Request();
        $client = Mockery::mock(Client::class);
        $client->allows()
            ->request('POST', 'https://example.com', Mockery::type('array'))
            ->andThrow($this->badResponseException('bad-response'));
        $request->setClient($client);

        try {
            $res = $request->request('POST', 'https://example.com');
        } catch (\Throwable $e) {
            $exception = $e;
        }

        $this->assertInstanceOf(RequestException::class, $exception);
        $this->assertInstanceOf(Response::class, $exception->getResponse());
    }

    public function testFinnalException()
    {
        $this->expectException(RequestException::class);
        $request = new Request();
        $request->request('GET', 'abc://xxx');
    }
}
