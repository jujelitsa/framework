<?php

namespace jujelitsa\framework\event_dispatcher;

use jujelitsa\framework\event_dispatcher\Message;

interface ObserverInterface
{
    /**
     * @param Message $message
     */
    public function observe(Message $message): void;
}
