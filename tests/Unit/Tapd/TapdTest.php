<?php

namespace ThirtyThree\Tests\Unit\Tapd;

use Mockery;
use GuzzleHttp\Client;
use ThirtyThree\Tapd\Tapd;
use GuzzleHttp\Psr7\Response;
use ThirtyThree\Tests\TestCase;
use ThirtyThree\Exceptions\RequestException;

class TapdTest extends TestCase
{
    public function testRequestException()
    {
        try {
            $tapd = new Tapd();
            $client = Mockery::mock(Client::class);
            $tapd->setClient($client);
            $responseJson = ['info' => 'mock error message'];
            $client->shouldReceive('request')
                ->with('GET', 'iterations', Mockery::any())
                ->once()
                ->andThrow($this->badResponseException(json_encode($responseJson)));

            $result = $tapd->iterations([]);
        } catch (RequestException $e) {
            $this->assertEquals($responseJson['info'], $e->getMessage());
        }
    }

    public function testIterationsApi()
    {
        $tapd = new Tapd();
        $client = Mockery::mock(Client::class);
        $tapd->setClient($client);
        $responseJson = ['data' => 'mock result'];
        $client->shouldReceive('request')
            ->with('GET', 'iterations', Mockery::any())
            ->once()
            ->andReturns(new Response(200, [], json_encode($responseJson)));

        $result = $tapd->iterations([]);
        $this->assertEquals($responseJson['data'], $result);
    }

    public function testStoriesApi()
    {
        $tapd = new Tapd();
        $client = Mockery::mock(Client::class);
        $tapd->setClient($client);
        $responseJson = ['data' => 'mock result'];
        $client->shouldReceive('request')
            ->with('GET', 'stories', Mockery::any())
            ->once()
            ->andReturns(new Response(200, [], json_encode($responseJson)));

        $result = $tapd->stories([]);
        $this->assertEquals($responseJson['data'], $result);
    }

    public function testStoriesCountApi()
    {
        $tapd = new Tapd();
        $client = Mockery::mock(Client::class);
        $tapd->setClient($client);
        $responseJson = ['data' => 'mock result'];
        $client->shouldReceive('request')
            ->with('GET', 'stories/count', Mockery::any())
            ->once()
            ->andReturns(new Response(200, [], json_encode($responseJson)));

        $result = $tapd->storiesCount([]);
        $this->assertEquals($responseJson['data'], $result);
    }

    public function testCompanyProjectsApi()
    {
        $tapd = new Tapd();
        $client = Mockery::mock(Client::class);
        $tapd->setClient($client);
        $responseJson = ['data' => 'mock result'];
        $client->shouldReceive('request')
            ->with('GET', 'workspaces/projects', Mockery::any())
            ->once()
            ->andReturns(new Response(200, [], json_encode($responseJson)));

        $result = $tapd->companyProjects([]);
        $this->assertEquals($responseJson['data'], $result);
    }
}
