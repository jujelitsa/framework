<?php

namespace jujelitsa\framework\http\router;

class Resource
{
    public function __construct(
        private readonly string $name,
        private readonly string $controller,
        private readonly array $config = []
    ) {}

    /**
     * @param HTTPRouterInterface $router
     * @return void
     */
    public function build(HTTPRouterInterface $router): void
    {
        $routes = $this->applyCustomConfig();

        foreach ($routes as $routeConfig) {
            $this->registerRoute($router, $routeConfig);
        }
    }

    private function applyCustomConfig(): array
    {
        $defaultConfig = [
            'index' => [
                'method' => 'GET',
                'path' => $this->name,
                'action' => 'actionList',
                'middleware' => [],
            ],
            'view' => [
                'method' => 'GET',
                'path' => "{$this->name}/{:id}",
                'action' => 'actionView',
                'middleware' => [],
            ],
            'create' => [
                'method' => 'POST',
                'path' => $this->name,
                'action' => 'actionCreate',
                'middleware' => [],
            ],
            'put' => [
                'method' => 'PUT',
                'path' => "{$this->name}/{:id}",
                'action' => 'actionUpdate',
                'middleware' => [],
            ],
            'patch' => [
                'method' => 'PATCH',
                'path' => "{$this->name}/{:id}",
                'action' => 'actionPatch',
                'middleware' => [],
            ],
            'delete' => [
                'method' => 'DELETE',
                'path' => "{$this->name}/{:id}",
                'action' => 'actionDelete',
                'middleware' => [],
            ],
        ];

        foreach ($this->config as $method => $definition) {

            if (isset($defaultConfig[$method]) === false) {
                continue;
            }

            if (isset($definition['middleware']) === true) {
                $defaultConfig[$method]['middleware'] = $definition['middleware'];
            }

        }

        return $defaultConfig;
    }

    private function registerRoute(HTTPRouterInterface $router, array $config): void
    {
        $route = $router->add(
            $config['method'],
            '/' . $config['path'],
            $this->controller . '::' . $config['action']
        );

        foreach ($config['middleware'] as $middleware) {
            $route->addMiddleware($middleware);
        }
    }
}
