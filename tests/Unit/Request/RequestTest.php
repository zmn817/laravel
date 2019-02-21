<?php

namespace ThirtyThree\Tests\Unit\Reqeust;

use Monolog\Logger;
use ThirtyThree\Tests\TestCase;
use ThirtyThree\Request\Request;

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
}
