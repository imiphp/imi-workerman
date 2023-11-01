<?php

declare(strict_types=1);

namespace Imi\Workerman\Test\AppServer\UdpServer\Error;

use Imi\Bean\Annotation\Bean;
use Imi\Server\UdpServer\Error\IUdpRouteNotFoundHandler;
use Imi\Server\UdpServer\IPacketHandler;
use Imi\Server\UdpServer\Message\IPacketData;

#[Bean(name: 'UdpRouteNotFound')]
class RouteNotFound implements IUdpRouteNotFoundHandler
{
    /**
     * {@inheritDoc}
     */
    public function handle(IPacketData $data, IPacketHandler $handler)
    {
        return 'gg';
    }
}
