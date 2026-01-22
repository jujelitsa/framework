<?php

namespace framework\http\errorHandler\strategy;

use framework\EnvConfigurationStorage;
use framework\http\contracts\RenderStrategyInterface;
use framework\storage\DebugTagStorageInterface;
use framework\view\View;
use framework\view\ViewRendererInterface;

class HtmlRenderStrategy implements RenderStrategyInterface
{
    public function __construct(
        private readonly DebugTagStorageInterface $debugTagStorage,
        private readonly ViewRendererInterface $view,
        private readonly string $envMode,
        private readonly bool $debugTag,
    ) {}

    public function render(\Throwable $exception): string
    {
        return $this->view->render('index', [
            'exception' => $exception,
            'envMode' => $this->envMode,
            'debugMode' => $this->debugTag,
            'debugTag' => $this->debugTagStorage->getTag(),
        ]);
    }
}