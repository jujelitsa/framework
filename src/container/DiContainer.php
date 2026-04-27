<?php

namespace jujelitsa\framework\container;

use LogicException;
use ReflectionMethod;

class DiContainer implements ContainerInterface
{
    private static ?self $instance = null;

    private array $singletons = [];

    protected function __construct(private array $config = []) {}

    /**
     * Запрещает клонирование объекта, являющегося синглтоном
     *
     * @throws LogicException
     */
    public function __clone(): void
    {
        throw new LogicException('Клонирование запрещено');
    }

    /**
     * Именованный конструктор
     * Создает экземпляр класса DIContainer
     *
     * @param array $config Массив конфигурации
     * @return self экземпляр класса DIContainer
     */
    public static function create(array $config = []): self
    {
        if (self::$instance !== null) {
            throw new LogicException('DIContainer уже сконструирован. Повторное создание запрещено.');
        }

        self::$instance = new self($config);

        return self::$instance;
    }

    /**
     * Создаёт экземпляр класса по имени
     *
     * @param string $dependencyName Имя класса
     * @param array $args Аргументы конструктора
     * @return object
     */
    public function build(string $dependencyName, array $args = []): object
    {
        if ($dependencyName === self::class || $dependencyName === ContainerInterface::class) {
            return $this;
        }

        if (function_exists($dependencyName) === true) {
            return $dependencyName($this);
        }

        $reflector = new \ReflectionClass($dependencyName);
        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $dependencyName();
        }

        $parameters = $constructor->getParameters();
        $resolvedArgs = [];

        foreach ($parameters as $param) {
            $name = $param->getName();

            if (array_key_exists($name, $args) === true) {
                $resolvedArgs[] = $args[$name];
                continue;
            }

            $type = $param->getType();

            if (($type === null || $type->isBuiltin() === true) && $param->isDefaultValueAvailable()) {
                $resolvedArgs[] = $param->getDefaultValue();
                continue;
            }

            $className = $type->getName();

            try {
                $resolvedArgs[] = $this->get($className);
                continue;
            } catch (\Throwable $e) {
                if ($param->isDefaultValueAvailable() === true) {
                    $resolvedArgs[] = $param->getDefaultValue();
                    continue;
                }

                throw new DependencyNotFoundException('Не удалось разрешить зависимость ' . $className . ' для параметра ' . $name);
            }
        }

        return $reflector->newInstanceArgs($resolvedArgs);
    }

    /**
     * Возвращает сервис или значение по ID
     *
     * @param string $id Идентификатор зависимости
     * @return mixed
     */
    public function get(string $id): object
    {
        if ($id === self::class || $id === ContainerInterface::class) {
            return $this;
        }

        if (isset($this->config['singletons'][$id]) === true) {
            return $this->resolveSingleton($id);
        }

        if (isset($this->config['definitions'][$id]) === true) {
            $definition = $this->config['definitions'][$id];

            if (($definition instanceof \Closure) === true) {
                $instance = $definition($this);
            }

            if (is_string($definition) === true) {
                $instance = $this->build($definition);
            }

            if (is_object($instance) === false) {
                throw new \RuntimeException("Зависимость '$id' должна разрешаться в объект.");
            }

            return $instance;
        }

        if (class_exists($id) === true) {
            return $this->build($id);
        }

        throw new DependencyNotFoundException($id);
    }

    /**
     * Вызывает метод с внедрением зависимостей
     */
    public function call(object|string $handler, string $method, array $args = []): mixed
    {
        $instance = is_string($handler) === true ? $this->get($handler) : $handler;

        $reflector = new ReflectionMethod($instance, $method);

        $parameters = $reflector->getParameters();
        $resolvedArgs = [];

        foreach ($parameters as $param) {

            if (array_key_exists($param->getName(), $args) === true) {

                $resolvedArgs[] = $args[$param->getName()];
                continue;
            }

            $isBuiltin = $param->getType() === null || $param->getType()->isBuiltin();

            if ($isBuiltin === true) {
                $this->resolveBuiltinParam($param, $param->getName(), $resolvedArgs);
                continue;
            }

            $className = $param->getType()->getName();

            $resolvedArgs[] = $this->get($className);
        }

        return $reflector->invokeArgs($instance, $resolvedArgs);
    }

    public function has(string $id): bool
    {
        return isset($this->config['singletons'][$id]) === true
            || isset($this->config['definitions'][$id]) === true
            || class_exists($id) === true;
    }

    private function resolveBuiltinParam(\ReflectionParameter $param, string $name, array &$resolvedArgs): void
    {
        if ($param->isDefaultValueAvailable() === false) {
            throw new \RuntimeException(
                sprintf('Не хватает значения для параметра $%s', $name)
            );
        }

        $resolvedArgs[] = $param->getDefaultValue();
    }

    private function resolveSingleton(string $id): object
    {
        if (isset($this->singletons[$id]) === true) {
            return $this->singletons[$id];
        }

        if (isset($this->config['singletons'][$id]) === false) {
            throw new DependencyNotFoundException($id);
        }

        $definition = $this->config['singletons'][$id];

        if (($definition instanceof \Closure) === true) {
            $instance = $definition($this);
        }
        if (is_string($definition) === true) {
            $instance = $this->build($definition);
        }

        $this->singletons[$id] = $instance;

        return $instance;
    }

    private function resolveValue(mixed $value): mixed
    {
        if (is_array($value) === true) {
            $result = [];
            foreach ($value as $k => $v) {
                $result[$k] = $this->resolveValue($v);
            }
            return $result;
        }

        if (($value instanceof \Closure) === true) {
            return $value($this);
        }

        return $value;
    }
}
