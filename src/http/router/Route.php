<?php

namespace framework\http\router;

class Route implements MiddlewareAssignable
{
    public string $method;
    public string $path;
    public array $params;
    private array $middlewares = [];
    private $handler;

    public function __construct (
        string $method,
        string $path,
        array $params,
        string|callable $handler
    )
    {
        $this->method = $method;
        $this->path = $path;
        $this->params = $params;
        $this->handler = $handler;
    }

    public function addMiddleware(callable|string $middleware): MiddlewareAssignable
    {
        if (is_callable($middleware) === true) {
            $this->middlewares[] = $middleware;
            return $this;
        }

        if (is_subclass_of($middleware, MiddlewareInterface::class) === true) {
            $this->middlewares[] = $middleware;
            return $this;
        }

        throw new \InvalidArgumentException($middleware . ' не реализует интерфейс - ' . MiddlewareInterface::class);
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getHandler(): callable|string
    {
        return $this->handler;
    }
}