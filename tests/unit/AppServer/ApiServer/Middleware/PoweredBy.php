<?php

declare(strict_types=1);

namespace Imi\Workerman\Test\AppServer\ApiServer\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 增加一个响应头，仅作演示，生产环境请去除.
 */
class PoweredBy implements MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request)->withAddedHeader('X-Powered-By', 'imiphp.com');
    }
}
