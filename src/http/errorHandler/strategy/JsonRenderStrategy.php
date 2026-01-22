<?php

namespace framework\http\errorHandler\strategy;

use framework\http\contracts\RenderStrategyInterface;
use framework\storage\DebugTagStorageInterface;

class JsonRenderStrategy implements RenderStrategyInterface
{
    public function __construct(
        private readonly DebugTagStorageInterface $debugTagStorage,
    ) {}

    public function render(\Throwable $exception): string
    {
        return json_encode([
            'massage' => $exception->getMessage(),
            'x-debug-tag' => $this->debugTagStorage->getTag(),
        ], JSON_UNESCAPED_UNICODE);
    }
}