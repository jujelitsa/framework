<?php

namespace jujelitsa\framework\logger;

class DTO
{
    public string $index;
    public ?string $category = null;
    public array $context = [];
    public int $level;
    public string $level_name;
    public string $action;
    public string $action_type;
    public string $datetime;
    public string $timestamp;
    public ?int $userId = null;
    public ?string $ip = null;
    public ?string $real_ip = null;
    public string $x_debug_tag;
    public string $message;
    public ?array $exception = null;
    public ?string $extras = null;
}