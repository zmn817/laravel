<?php

namespace ThirtyThree\Tests\Unit\Reqeust;

use Mockery;
use GuzzleHttp\Client;
use ThirtyThree\Amap\Amap;
use GuzzleHttp\Psr7\Response;
use ThirtyThree\Tests\TestCase;
use ThirtyThree\Exceptions\RequestException;

class AmapTest extends TestCase
{
    public function testRequestException()
    {
        try {
            $tapd = new Amap();
            $client = Mockery::mock(Client::class);
            $tapd->setClient($client);
            $responseJson = ['status' => '0', 'info' => 'INVALID_USER_KEY', 'infocode' => '10001'];
            $client->shouldReceive('request')
                ->with('GET', 'v3/config/district', Mockery::any())
                ->once()
                ->andReturn(new Response(200, [], json_encode($responseJson)));

            $tapd->district([]);
        } catch (RequestException $e) {
            $this->assertEquals($responseJson['info'], $e->getMessage());
        }
    }

    public function testDistrictApi()
    {
        $tapd = new Amap();
        $client = Mockery::mock(Client::class);
        $tapd->setClient($client);
        $responseJson = ['status' => '1', 'data' => 'mock result'];
        $client->shouldReceive('request')
            ->with('GET', 'v3/config/district', Mockery::any())
            ->once()
            ->andReturns(new Response(200, [], json_encode($responseJson)));

        $result = $tapd->district([]);

        $this->assertEquals($responseJson, $result);
    }

    public function testGeoApi()
    {
        $tapd = new Amap();
        $client = Mockery::mock(Client::class);
        $tapd->setClient($client);
        $responseJson = ['status' => '1', 'data' => 'mock result'];
        $client->shouldReceive('request')
            ->with('GET', 'v3/geocode/geo', Mockery::any())
            ->once()
            ->andReturns(new Response(200, [], json_encode($responseJson)));

        $result = $tapd->geo([]);

        $this->assertEquals($responseJson, $result);
    }
}
