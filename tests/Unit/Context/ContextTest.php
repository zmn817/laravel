<?php

namespace ThirtyThree\Tests\Feature\Unit;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ThirtyThree\Tests\TestCase;
use ThirtyThree\Context\Middleware\AddRequestId;

class ContextTest extends TestCase
{
    public function testHasRequestIdHeaderInResponse()
    {
        $request = Request::create('/', 'GET');

        $middleware = new AddRequestId();

        $response = $middleware->handle($request, function () {
            return new Response();
        });

        $this->assertArrayHasKey('x-request-id', $response->headers->all());
    }
}
