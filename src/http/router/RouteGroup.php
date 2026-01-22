<?php

namespace framework\http\router;

class RouteGroup implements MiddlewareAssignable
{
    private $middlewares = [];
    private array $routes = [];
    private array $groups = [];

    public function __construct(
        public readonly string $name,
    ) {}

    public function addMiddleware(callable|string $middleware): MiddlewareAssignable
    {
        foreach ($this->groups as $group) {
            $group->addMiddleware($middleware);
        }

        foreach ($this->routes as $route) {
            $route->addMiddleware($middleware);
        }

        $this->middlewares[] = $middleware;
        return $this;
    }

    public function addRoute(Route $route): void
    {
        $this->routes[] = $route;
        foreach ($this->middlewares as $middleware) {
            $route->addMiddleware($middleware);
        }
    }

    public function addGroup(RouteGroup $group): void
    {
        $this->groups[] = $group;
        foreach ($this->middlewares as $middleware) {
            $group->addMiddleware($middleware);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

}