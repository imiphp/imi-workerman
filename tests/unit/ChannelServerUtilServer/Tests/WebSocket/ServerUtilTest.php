<?php

declare(strict_types=1);

namespace Imi\Workerman\Test\ChannelServerUtilServer\Tests\WebSocket;

use Wrench\Client;
use Yurun\Util\HttpRequest;

/**
 * @testdox Imi\Workerman\Server\Server
 */
class ServerUtilTest extends BaseTest
{
    public function testGetServer(): void
    {
        $this->go(function (): void {
            $http = new HttpRequest();
            $response = $http->get($this->httpHost . 'serverUtil/getServer');
            $this->assertEquals([
                'null'      => 'http',
                'http'      => 'http',
                'notFound'  => true,
            ], $response->json(true));
        });
    }

    public function testSendMessage(): void
    {
        $this->go(function (): void {
            $http = new HttpRequest();
            $response = $http->get($this->httpHost . 'serverUtil/sendMessage');
            $all = 'Linux' === \PHP_OS ? 2 : 1;
            $this->assertEquals([
                'sendMessageAll'    => $all,
                'sendMessage1'      => 1,
                'sendMessage2'      => 2,
                'sendMessageRawAll' => $all,
                'sendMessageRaw1'   => 1,
                'sendMessageRaw2'   => 2,
            ], $response->json(true));
        });
    }

    public function testSend(): void
    {
        $client1 = $client2 = $client3 = null;
        $this->go(function () use (&$client1, &$client2, &$client3): void {
            $client1 = new Client($this->host, $this->httpHost);
            $this->assertTrue($client1->connect());

            $client2 = new Client($this->host, $this->httpHost);
            $this->assertTrue($client2->connect());

            $client3 = new Client($this->host, $this->httpHost);
            $this->assertTrue($client3->connect());

            sleep(1);

            $this->assertTrue($client1->sendData(json_encode([
                'action'    => 'info',
            ])));
            $recvData1 = $client1->receive();
            $recv = reset($recvData1)->getPayload();
            $recvData1 = json_decode($recv, true);
            $this->assertTrue(isset($recvData1['clientId']));

            $this->assertTrue($client2->sendData(json_encode([
                'action'    => 'info',
            ])));
            $recvData2 = $client2->receive();
            $recv = reset($recvData2)->getPayload();
            $recvData2 = json_decode($recv, true);
            $this->assertTrue(isset($recvData2['clientId']));

            $this->assertTrue($client3->sendData(json_encode([
                'action'    => 'login',
                'username'  => 'testSend',
            ])));
            $recvData3 = $client3->receive();
            $recv = reset($recvData3)->getPayload();
            $recvData3 = json_decode($recv, true);
            $this->assertTrue($recvData3['success'] ?? null);

            $http = new HttpRequest();
            $response = $http->post($this->httpHost . 'serverUtil/send', [
                'flag' => 'testSend',
            ], 'json');
            $this->assertEquals([
                'sendByFlag'    => 1,
                'sendRawByFlag' => 1,
                // Workerman LocalServerUtil 跨进程只支持返回 1/0
                'sendToAll'     => 1,
                'sendRawToAll'  => 1,
            ], $response->json(true));

            sleep(1);

            $expectedData = json_encode(['data' => 'test']);
            $count = 0;
            for ($i = 0; $i < 2 && $count < 2; ++$i)
            {
                $this->assertIsArray($recvDatas = $client1->receive());
                foreach ($recvDatas as $recvData)
                {
                    $recv = $recvData->getPayload();
                    if ($expectedData === $recv)
                    {
                        ++$count;
                    }
                }
            }
            $this->assertEquals(2, $count);

            $count = 0;
            for ($i = 0; $i < 2 && $count < 2; ++$i)
            {
                $this->assertIsArray($recvDatas = $client2->receive());
                foreach ($recvDatas as $recvData)
                {
                    $recv = $recvData->getPayload();
                    if ($expectedData === $recv)
                    {
                        ++$count;
                    }
                }
            }
            $this->assertEquals(2, $count);

            $count = 0;
            for ($i = 0; $i < 4 && $count < 4; ++$i)
            {
                $this->assertIsArray($recvDatas = $client3->receive());
                foreach ($recvDatas as $recvData)
                {
                    $recv = $recvData->getPayload();
                    if ($expectedData === $recv)
                    {
                        ++$count;
                    }
                }
            }
            $this->assertEquals(4, $count);
        }, static function () use (&$client1, &$client2, &$client3): void {
            if ($client1)
            {
                $client1->disconnect();
            }
            if ($client2)
            {
                $client2->disconnect();
            }
            if ($client3)
            {
                $client3->disconnect();
            }
        });
    }

    public function testSendToGroup(): void
    {
        /** @var Client[] $clients */
        $clients = [];
        $this->go(function () use (&$clients): void {
            for ($i = 0; $i < 2; ++$i)
            {
                $clients[] = $client = new Client($this->host, $this->httpHost);
                $this->assertTrue($client->connect());
            }

            sleep(1);

            foreach ($clients as $client)
            {
                $this->assertTrue($client->sendData(json_encode([
                    'action'    => 'login',
                    'username'  => uniqid('', true),
                ])));
                $recvData = $client->receive();
                $recv = reset($recvData)->getPayload();
                $recvData = json_decode($recv, true);
                $this->assertTrue($recvData['success'] ?? null);
            }

            $http = new HttpRequest();
            $response = $http->get($this->httpHost . 'serverUtil/sendToGroup');

            $this->assertEquals([
                'sendToGroup'    => 1,
                'sendRawToGroup' => 1,
            ], $response->json(true));

            sleep(1);

            $expectedData = json_encode(['data' => 'test']);
            foreach ($clients as $client)
            {
                $count = 0;
                for ($i = 0; $i < 2 && $count < 2; ++$i)
                {
                    $this->assertIsArray($recvDatas = $client->receive());
                    foreach ($recvDatas as $recvData)
                    {
                        $recv = $recvData->getPayload();
                        if ($expectedData === $recv)
                        {
                            ++$count;
                        }
                    }
                }
                $this->assertEquals(2, $count);
            }
        }, static function () use (&$clients): void {
            foreach ($clients as $client)
            {
                $client->disconnect();
            }
        });
    }

    public function testClose(): void
    {
        $client1 = $client2 = null;
        try
        {
            $client1 = new Client($this->host, $this->httpHost);
            $this->assertTrue($client1->connect());
            $client2 = new Client($this->host, $this->httpHost);
            $this->assertTrue($client2->connect());

            sleep(1);

            $this->assertTrue($client1->sendData(json_encode([
                'action'    => 'info',
            ])));
            $recvData = $client1->receive();
            $recv = reset($recvData)->getPayload();
            $recvData1 = json_decode($recv, true);
            $this->assertTrue(isset($recvData1['clientId']), 'Not found clientId');

            $this->assertTrue($client2->sendData(json_encode([
                'action'    => 'login',
                'username'  => 'testClose',
            ])));
            $recvData = $client2->receive();
            $recv = reset($recvData)->getPayload();
            $recvData2 = json_decode($recv, true);
            $this->assertTrue($recvData2['success'] ?? null, 'Not found success');

            $http3 = new HttpRequest();
            $response = $http3->post($this->httpHost . 'serverUtil/close', ['flag' => 'testClose']);
            $this->assertEquals([
                'flag'     => 1,
            ], $response->json(true));
            $this->assertEquals('', $client1->receive());
            $this->assertEquals('', $client2->receive());
        }
        catch (\Throwable $th)
        {
            if ($client1)
            {
                $client1->disconnect();
            }
            if ($client2)
            {
                $client2->disconnect();
            }
            throw $th;
        }
    }
}
