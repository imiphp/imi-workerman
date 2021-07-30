<?php

declare(strict_types=1);

namespace Imi\Workerman\Server\Http;

use Imi\App;
use Imi\Bean\Annotation\Bean;
use Imi\Event\Event;
use Imi\RequestContext;
use Imi\Server\Protocol;
use Imi\Util\ImiPriority;
use Imi\Workerman\Http\Message\WorkermanRequest;
use Imi\Workerman\Http\Message\WorkermanResponse;
use Imi\Workerman\Server\Base;
use Imi\Workerman\Server\Http\Listener\BeforeRequest;
use Imi\Workerman\Server\Protocol\WorkermanHttp;
use Workerman\Connection\ConnectionInterface;
use Workerman\Protocols\Http\Response;

/**
 * @Bean("WorkermanHttpServer")
 */
class Server extends Base
{
    /**
     * 构造方法.
     */
    public function __construct(string $name, array $config)
    {
        parent::__construct($name, $config);
        $this->worker->protocol = WorkermanHttp::class;
    }

    /**
     * 获取协议名称.
     */
    public function getProtocol(): string
    {
        return Protocol::HTTP;
    }

    /**
     * 是否为长连接服务
     */
    public function isLongConnection(): bool
    {
        return false;
    }

    /**
     * 绑定服务器事件.
     */
    protected function bindEvents(): void
    {
        parent::bindEvents();
        if (!App::get('has_imi_workerman_http_request_event', false))
        {
            Event::on('IMI.WORKERMAN.SERVER.HTTP.REQUEST', [new BeforeRequest(), 'handle'], ImiPriority::IMI_MAX);
            App::set('has_imi_workerman_http_request_event', true);
        }
        $this->worker->onMessage = function (ConnectionInterface $connection, $data) {
            try
            {
                $worker = $this->worker;
                // @phpstan-ignore-next-line
                $request = new WorkermanRequest($worker, $connection, $data);
                // @phpstan-ignore-next-line
                $response = new WorkermanResponse($worker, $connection, new Response());
                RequestContext::muiltiSet([
                    'server'   => $this,
                    'request'  => $request,
                    'response' => $response,
                ]);
                Event::trigger('IMI.WORKERMAN.SERVER.HTTP.REQUEST', [
                    'server'   => $this,
                    'request'  => $request,
                    'response' => $response,
                ], $this);
            }
            catch (\Throwable $th)
            {
                if (true !== $this->getBean('HttpErrorHandler')->handle($th))
                {
                    App::getBean('ErrorLog')->onException($th);
                }
            }
            finally
            {
                RequestContext::destroy();
            }
        };
    }

    /**
     * 获取实例化 Worker 用的协议.
     */
    protected function getWorkerScheme(): string
    {
        return 'http';
    }
}
