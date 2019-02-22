<?php

namespace ThirtyThree\Tests\Unit\Reqeust;

use Monolog\Logger;
use Tests\TestCase;
use Illuminate\Support\Str;
use GuzzleHttp\Psr7\Response;
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
        $request->logWhenSuccess();

        $path = Str::random();
        $name = Str::random();
        $logger = fileLogger($path, $name);
        $request->setLogger($logger);
        $path = 'logs/'.trim($path).'/';
        $name = $name.'-'.date('Y-m-d');

        $request->request('GET', 'https://httpbin.org/get');
        $content = file_get_contents(storage_path($path.$name.'.log'));
        $this->assertNotEmpty($content);

        $request->doNotLogWhenSuccess();
        $request->request('GET', 'https://httpbin.org/get');
        $same = file_get_contents(storage_path($path.$name.'.log'));
        $this->assertEquals($content, $same);
    }

    public function testRequestException()
    {
        $exception = null;
        $request = new Request();
        try {
            $request->request('POST', 'https://httpbin.org/get');
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
