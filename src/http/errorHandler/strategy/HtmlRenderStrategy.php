<?php

namespace jujelitsa\framework\http\errorHandler\strategy;

use jujelitsa\framework\debugTagStorage\DebugTagStorageInterface;
use jujelitsa\framework\http\contracts\RenderStrategyInterface;
use jujelitsa\framework\view\ViewRendererInterface;

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