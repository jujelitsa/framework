<?php

namespace jujelitsa\framework\event_dispatcher;

final class EventPublisherDTO
{
    public function __construct(
        public string $channel,
        public array $payload,
        public readonly ?string $key = null,
    ) {}
}