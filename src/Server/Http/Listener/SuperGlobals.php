<?php

declare(strict_types=1);

namespace Imi\Workerman\Server\Http\Listener;

use Imi\App;
use Imi\Bean\Annotation\Listener;
use Imi\Event\EventParam;
use Imi\Event\IEventListener;

#[Listener(eventName: 'IMI.WORKERMAN.SERVER.WORKER_START', one: true)]
class SuperGlobals implements IEventListener
{
    /**
     * {@inheritDoc}
     */
    public function handle(EventParam $e): void
    {
        /** @var \Imi\Server\Http\SuperGlobals\Listener\SuperGlobals $superGlobals */
        $superGlobals = App::getBean('SuperGlobals');
        $superGlobals->bind();
    }
}
