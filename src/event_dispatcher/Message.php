<?php

namespace jujelitsa\framework\event_dispatcher;

class Message
{
    public function __construct(private mixed $content) {}

    public function getContent(): mixed
    {
        return $this->content;
    }
}