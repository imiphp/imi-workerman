<?php

declare(strict_types=1);

namespace Imi\Workerman\Server\Udp;

use Imi\Bean\Annotation\Bean;
use Imi\Event\Event;
use Imi\Log\Log;
use Imi\RequestContext;
use Imi\Server\Protocol;
use Imi\Server\UdpServer\Contract\IUdpServer;
use Imi\Workerman\Server\Base;
use Imi\Workerman\Server\Udp\Message\PacketData;
use Workerman\Connection\UdpConnection;

/**
 * @Bean("WorkermanUdpServer")
 */
class Server extends Base implements IUdpServer
{
    /**
     * {@inheritDoc}
     */
    public function getProtocol(): string
    {
        return Protocol::UDP;
    }

    /**
     * {@inheritDoc}
     */
    public function isLongConnection(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function bindEvents(): void
    {
        parent::bindEvents();

        $this->worker->onMessage = function (UdpConnection $connection, string $data) {
            try
            {
                $requestContext = RequestContext::getContext();
                $requestContext['server'] = $this;
                $requestContext['connection'] = $connection;
                $packetData = $requestContext['packetData'] = new PacketData($connection, $data);
                Event::trigger('IMI.WORKERMAN.SERVER.UDP.MESSAGE', [
                    'server'     => $this,
                    'connection' => $connection,
                    'data'       => $data,
                    'packetData' => $packetData,
                ], $this);
            }
            catch (\Throwable $th)
            {
                // @phpstan-ignore-next-line
                if (true !== $this->getBean('UdpErrorHandler')->handle($th))
                {
                    Log::error($th);
                }
            }
        };
    }

    /**
     * {@inheritDoc}
     */
    protected function getWorkerScheme(): string
    {
        return 'udp';
    }

    /**
     * {@inheritDoc}
     */
    public function sendTo(string $ip, int $port, string $data): bool
    {
        $connection = new UdpConnection($this->worker->getMainSocket(), $ip . ':' . $port);

        return (bool) $connection->send($data, true);
    }
}
