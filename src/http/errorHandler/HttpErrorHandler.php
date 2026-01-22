<?php

namespace framework\http\errorHandler;

use framework\container\ContainerInterface;
use framework\contracts\ErrorHandlerInterface;
use framework\http\contracts\RenderStrategyInterface;
use framework\http\enum\ContentTypeEnum;
use framework\http\errorHandler\strategy\HtmlRenderStrategy;
use framework\http\errorHandler\strategy\JsonRenderStrategy;
use framework\http\errorHandler\strategy\RenderStrategyNotFoundException;

class HttpErrorHandler implements ErrorHandlerInterface
{
    private array $renderStrategies = [
        ContentTypeEnum::HTML->value => HtmlRenderStrategy::class,
        ContentTypeEnum::JSON->value => JsonRenderStrategy::class,
    ];

    public function __construct(
        private readonly ContainerInterface $container,
        private string $mode = ContentTypeEnum::HTML->value,
    ) {}

    public function handle(\Throwable $e): string
    {
        return $this->container->call($this->renderStrategies[$this->mode], 'render', ['exception' => $e]);
    }

    public function switchContentTypeMode(string $mode): void
    {
        if (isset($this->renderStrategies[$this->mode]) === false) {
            throw new RenderStrategyNotFoundException('Стратегия рендеринга для режима ' . $this->mode . ' не найдена.');
        }

        $this->mode = $mode;
    }

    public function isContentType(string $mode): bool
    {
        return $this->mode === $mode;
    }

    public function addRenderStrategy(string $contentType, string $strategyClass): void
    {
        if (is_subclass_of($strategyClass, RenderStrategyInterface::class) === false) {
            throw new RenderStrategyNotFoundException(
                "Стратегия должна реализовывать RenderStrategyInterface"
            );
        }

        $this->renderStrategies[$contentType] = $strategyClass;
    }
}