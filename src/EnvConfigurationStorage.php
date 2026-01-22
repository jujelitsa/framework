<?php

namespace framework;

class EnvConfigurationStorage
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __clone(): void
    {
        throw new \LogicException('Клонирование запрещено');
    }

    public static function create(array $config = []): self
    {
        if (self::$instance !== null) {
            throw new \LogicException('EnvConfigurationStorage уже сконструирован. Повторное создание запрещено.');
        }

        self::$instance = new self($config);

        return self::$instance;
    }

    public function get(string $envName): string
    {
        return (getenv($envName));
    }


}