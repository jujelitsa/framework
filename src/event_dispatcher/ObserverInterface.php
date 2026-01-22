<?php

namespace framework\event_dispatcher;

use framework\event_dispatcher\Message;

interface ObserverInterface
{
    /**
     * @param Message $message
     */
    public function observe(Message $message): void;
}
