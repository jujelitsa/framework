<?php

namespace jujelitsa\framework\http\router;

use jujelitsa\framework\container\DiContainer;
use jujelitsa\framework\http\Exception\HttpNotFoundException;
use jujelitsa\framework\http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use jujelitsa\framework\http\Exception\HttpBadRequestException;

class Router implements HTTPRouterInterface, MiddlewareAssignable
{
    private array $routes = [];
    private array $groupPrefixStack = [];
    private array $groupStack = [];
    private array $middlewares = [];

    public function __construct(private DiContainer $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $route, string|callable $handler): Route
    {
        return $this->add('GET', $route, $handler);
    }

    /**
     * {@inheritdoc}
     */
    public function post(string $route, string|callable $handler): Route
    {
        return $this->add('POST', $route, $handler);
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $route, string|callable $handler): Route
    {
        return $this->add('PUT', $route, $handler);
    }

    /**
     * {@inheritdoc}
     */
    public function patch(string $route, string|callable $handler): Route
    {
        return $this->add('PATCH', $route, $handler);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $route, string|callable $handler): Route
    {
        return $this->add('DELETE', $route, $handler);
    }

    /**
     * {@inheritdoc}
     */
    public function group(string $name, callable $set): RouteGroup
    {
        $group = new RouteGroup($name);

        if (empty($this->groupStack) === false) {
            $parentGroup = end($this->groupStack);
            $parentGroup->addGroup($group);
        }

        $this->groupPrefixStack[] = $name;
        $this->groupStack[] = $group;

        try {
            $set($this);
        } finally {
            array_pop($this->groupPrefixStack);
            array_pop($this->groupStack);
        }

        return $group;
    }

    /**
     * Получение параметров запроса из маршрута
     *
     * @param  string $route маршрут
     * Пример:
     * "/path?{firstNumber}{?secondNumber=900}"
     * @return array
     * Пример:
     * [
     *     [
     *         'name' => 'firstNumber',
     *         'required' => true,
     *         'default' => null,
     *     ],
     *     [
     *         'name' => 'secondNumber',
     *         'required' => false,
     *         'default' => 900,
     *     ],
     * ]
     */
    private function prepareParams(string $route): array
    {

        $params = [];
        $pattern = '/\{(\?)?([a-zA-Z_][a-zA-Z0-9_]*)(?:\|([a-zA-Z]+))?(?:=([^}]*))?\}/';
        preg_match_all($pattern, $route, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $optional = $match[1] === '?';
            $name = $match[2];
            $type = $match[3] ?? 'string';
            $default = $optional && isset($match[4]) && $match[4] !== '' ? $match[4] : null;

            $params[] = [
                'name' => $name,
                'required' => !$optional,
                'default' => $default,
                'type' => $type,
            ];
        }

        return $params;
    }

    /**
     * Формирование массива параметров вызовов обработчика маршрута
     *
     * @param  string|callable $handler обработчик - коллбек функция
     * или неймспейс класса в формате 'Неймспейс::метод'
     * @return array
     * Пример для callable:
     * [$handler, null]
     * Пример для string:
     * ['Неймспейс', 'метод'];
     */
    private function resolveHandler(callable|string $handler): array
    {
        if (is_callable($handler) === true) {
            return [$handler, null];
        }

        if (is_string($handler) === true && str_contains($handler, '::') === true) {
            [$class, $method] = explode('::', $handler, 2);
            return [$class, $method];
        }

        throw new \InvalidArgumentException('Обработчик должен быть вызываемым или иметь формат "Class::method".');
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $method, string $route, string|callable $handler): Route
    {
        $parts = explode('?', $route, 2);
        $basePath = $parts[0];
        $paramString = isset($parts[1]) ? '?' . $parts[1] : '';

        $prefix = implode('/', $this->groupPrefixStack);
        $path = $prefix !== '' ? '/' . $prefix . $basePath : $basePath;

        $pathParams = $this->prepareParams($basePath);
        $queryParams = $paramString ? $this->prepareParams($paramString) : [];

        $params = array_merge($pathParams, $queryParams);

        $routeObj = new Route($method, $path, $params, $handler);

        if (empty($this->groupStack) === false) {
            $currentGroup = end($this->groupStack);
            $currentGroup->addRoute($routeObj);
        }

        $this->routes[$method][$path] = $routeObj;

        return $routeObj;
    }


    /**
     * Получение значений параметров запроса определенных для маршрута
     *
     * Пример:
     * "/path?{firstNumber}{?secondNumber=900}"
     * "/path?firstNumber=700"
     *
     * @param  array $queryParams параметры из запроса
     * @param  array $params подготовленные параметры определенных для запроса
     * @return array
     * Пример:
     * [700, 900]
     * @throws HttpBadRequestException если в строке запроса не передан параметр объявленный как обязательный
     */
    private function mapParams(array $queryParams, array $params): array
    {
        $values = [];

        foreach ($params as $param) {

            $hasValue = array_key_exists($param['name'], $queryParams);

            if ($hasValue === true) {
                $value = $queryParams[$param['name']];
                $values[$param['name']] = $this->castType($value, $param['type']);
                continue;
            }

            if ($hasValue === false && $param['required'] === false) {
                $values[$param['name']] = $param['default'];
                continue;
            }

            if ($hasValue === false && $param['required'] === true) {
                throw new HttpBadRequestException("Отсутствует обязательный параметр запроса: {$param['name']}");
            }
        }

        return $values;
    }

    private function castType(string $value, string $type): mixed
    {
        return match ($type) {
            'integer' => filter_var($value, FILTER_VALIDATE_INT)
                ?? throw new HttpBadRequestException("{$value} не является integer"),

            'float' => filter_var($value, FILTER_VALIDATE_FLOAT)
                ?? throw new HttpBadRequestException("{$value} не является float"),

            'string' => preg_match('/^[\p{L}]+$/u', $value)
                ? $value
                : throw new HttpBadRequestException("{$value} не является string"),

            default => throw new HttpBadRequestException("Неизвестный тип параметра: {$type}")
        };
    }


    public function dispatch(ServerRequestInterface $request): mixed
    {
        $method = $request->getMethod();
        $path   = $request->getUri()->getPath();

        if (!isset($this->routes[$method])) {
            throw new HttpNotFoundException("Метод {$method} не поддерживается.");
        }

        $route = null;
        $pathParams = [];

        foreach ($this->routes[$method] as $routeObj) {

            $pattern = $this->convertPathToRegex($routeObj->path, $routeObj->params);

            if (preg_match($pattern, $path, $matches)) {

                $route = $routeObj;

                foreach ($routeObj->params as $param) {
                    if (isset($matches[$param['name']])) {
                        $pathParams[$param['name']] =
                            $this->castType($matches[$param['name']], $param['type']);
                    }
                }

                break;
            }
        }

        if ($route === null) {
            throw new HttpNotFoundException("Маршрут не найден: {$method} {$path}");
        }

        $response = $this->container->get(ResponseInterface::class);

        $middlewares = array_merge($this->middlewares, $route->getMiddlewares());
        $this->runMiddlewares($middlewares, $request, $response);

        $queryParams = $request->getQueryParams();
        $allParams = array_merge($queryParams, $pathParams);

        $params = $this->mapParams($allParams, $route->params);
        $handler = $route->getHandler();

        if (is_callable($handler) === true) {
            return $handler($request, $response, ...array_values($params));
        }

        if (is_string($handler) === true && str_contains($handler, '::') === true) {

            [$class, $methodName] = explode('::', $handler, 2);

            $allParams = [
                'request'  => $request,
                'response' => $response,
            ];

            foreach ($params as $key => $value) {
                $allParams[$key] = $value;
            }

            return $this->container->call($class, $methodName, $allParams);
        }

        throw new \RuntimeException('Неверный тип handler.');
    }

    private function runMiddlewares(array $middlewares, ServerRequestInterface $request, ResponseInterface $response): void
    {
        $next = function (): void {};

        foreach (array_reverse($middlewares) as $middleware) {
            $next = function () use ($middleware, $request, $response, $next): void {
                if (is_callable($middleware) === true) {
                    $middleware($request, $response, $next);
                    return;
                }

                $this->container->get($middleware)->__invoke($request, $response, $next);
            };
        }

        $next();
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

    private function convertPathToRegex(string $path, array $params): string
    {
        foreach ($params as $param) {

            $typePattern = match ($param['type']) {
                'integer' => '\d+',
                'string'  => '[^/]+',
                default   => '[^/]+',
            };

            $path = preg_replace(
                '/\{' . $param['name'] . '\|[^}]+\}/',
                '(?P<' . $param['name'] . '>' . $typePattern . ')',
                $path
            );
        }

        return '#^' . $path . '$#';
    }

    public function addResource(string $name, string $controller, array $config = []): void
    {
        (new Resource($name, $controller, $config))->build($this);
    }

}
