<?php

namespace ThirtyThree\Tests\Unit\Boss;

use Mockery;
use GuzzleHttp\Client;
use ThirtyThree\Boss\Wechat;
use GuzzleHttp\Psr7\Response;
use ThirtyThree\Tests\TestCase;
use ThirtyThree\Exceptions\RequestException;

class WechatTest extends TestCase
{
    public function testRequestException()
    {
        try {
            $tapd = new Wechat();
            $client = Mockery::mock(Client::class);
            $tapd->setClient($client);
            $responseJson = ['rescode' => 0, 'resmsg' => 'mock exception'];
            $client->shouldReceive('request')
                ->with('GET', 'bzminiapp/geek/search/condition.json', Mockery::any())
                ->once()
                ->andReturn(new Response(200, [], json_encode($responseJson)));

            $tapd->condition([]);
        } catch (RequestException $e) {
            $this->assertEquals('mock exception', $e->getMessage());
        }

        try {
            $tapd = new Wechat();
            $client = Mockery::mock(Client::class);
            $tapd->setClient($client);
            $responseJson = ['rescode' => 0, 'resmsg' => 'mock exception'];
            $client->shouldReceive('request')
                ->with('GET', 'bzminiapp/geek/search/condition.json', Mockery::any())
                ->once()
                ->andThrow($this->badResponseException(json_encode($responseJson)));

            $tapd->condition([]);
        } catch (RequestException $e) {
            $this->assertEquals($responseJson['resmsg'], $e->getMessage());
        }
    }

    public function testConditionApi()
    {
        $tapd = new Wechat();
        $client = Mockery::mock(Client::class);
        $tapd->setClient($client);
        $responseJson = ['rescode' => 1, 'data' => 'mock result'];
        $client->shouldReceive('request')
            ->with('GET', 'bzminiapp/geek/search/condition.json', Mockery::any())
            ->once()
            ->andReturns(new Response(200, [], json_encode($responseJson)));

        $result = $tapd->condition([]);

        $this->assertEquals($responseJson['data'], $result);
    }

    public function testjdDetailApi()
    {
        $tapd = new Wechat();
        $client = Mockery::mock(Client::class);
        $tapd->setClient($client);
        $responseJson = ['rescode' => 1, 'data' => 'mock result'];
        $client->shouldReceive('request')
            ->with('GET', 'bzminiapp/geek/job/detail.json?jobId=1', Mockery::any())
            ->once()
            ->andReturns(new Response(200, [], json_encode($responseJson)));

        $result = $tapd->jdDetail(1);

        $this->assertEquals($responseJson['data'], $result);
    }

    public function testcompanyApi()
    {
        $tapd = new Wechat();
        $client = Mockery::mock(Client::class);
        $tapd->setClient($client);
        $responseJson = ['rescode' => 1, 'data' => 'mock result'];
        $client->shouldReceive('request')
            ->with('GET', 'bzminiapp/brand/detail.json?brandId=1', Mockery::any())
            ->once()
            ->andReturns(new Response(200, [], json_encode($responseJson)));

        $result = $tapd->company(1);

        $this->assertEquals($responseJson['data'], $result);
    }

    public function testsearchApi()
    {
        $tapd = new Wechat();
        $client = Mockery::mock(Client::class);
        $tapd->setClient($client);
        $responseJson = ['rescode' => 1, 'data' => 'mock result'];
        $client->shouldReceive('request')
            ->with('GET', 'bzminiapp/geek/search/joblist.json?a=1', Mockery::any())
            ->once()
            ->andReturns(new Response(200, [], json_encode($responseJson)));

        $result = $tapd->search(['a' => 1]);

        $this->assertEquals($responseJson['data'], $result);
    }
}
