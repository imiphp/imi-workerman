<?php

declare(strict_types=1);

namespace Imi\Workerman\Test\AppServer\Tests\WebSocket;

abstract class BaseTestCase extends \Imi\Workerman\Test\BaseTestCase
{
    /**
     * WebSocket 服务请求主机.
     *
     * @var string
     */
    protected $host = 'ws://127.0.0.1:13002/';

    /**
     * HTTP 服务请求主机.
     *
     * @var string
     */
    protected $httpHost = 'http://127.0.0.1:13000/';
}
