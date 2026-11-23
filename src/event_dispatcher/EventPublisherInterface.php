<?php

namespace jujelitsa\framework\event_dispatcher;

interface EventPublisherInterface
{
    public function handle(EventPublisherDTO $publisherDTO): void;
}