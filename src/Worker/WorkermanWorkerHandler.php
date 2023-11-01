<?php

declare(strict_types=1);

namespace Imi\Workerman\Worker;

use Imi\Bean\Annotation\Bean;
use Imi\RequestContext;
use Imi\Workerman\Contract\IWorkermanWorker;
use Imi\Workerman\Server\WorkermanServerWorker;
use Workerman\Worker;

#[Bean(name: 'WorkermanWorkerHandler')]
class WorkermanWorkerHandler implements IWorkermanWorker
{
    /**
     * 是否初始化完毕.
     */
    private bool $isInited = false;

    /**
     * Workerman 的 Worker 对象
     */
    private ?Worker $worker = null;

    /**
     * 获取 Workerman 的 Worker 对象
     */
    public function getWorker(): Worker
    {
        if (null === $this->worker)
        {
            return $this->worker = RequestContext::get('worker');
        }
        else
        {
            return $this->worker;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkerId(): ?int
    {
        return $this->getWorker()->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkerNum(): int
    {
        return $this->getWorker()->count;
    }

    /**
     * {@inheritDoc}
     */
    public function isInited(): bool
    {
        return $this->isInited;
    }

    /**
     * {@inheritDoc}
     */
    public function inited(): void
    {
        $this->isInited = true;
    }

    /**
     * {@inheritDoc}
     */
    public function getMasterPid(): int
    {
        return WorkermanServerWorker::getMasterPid();
    }
}
