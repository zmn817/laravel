<?php

namespace ThirtyThree\Tests;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase as BaseTest;
use GuzzleHttp\Exception\BadResponseException;

class TestCase extends BaseTest
{
    public function badResponseException($responseBody = null)
    {
        return new BadResponseException(
            '',
            new Request('GET', 'https://example.com'),
            new Response(500, [], $responseBody ?: json_encode(['error' => 'mock error message']))
        );
    }
}
