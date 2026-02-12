<?php

namespace jujelitsa\framework\configStorage;

final class ConfigStorage implements ConfigStorageInterface
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function get(string $key): ?string
    {
        if (getenv($key) !== false) {
            return getenv($key);
        }

        if (array_key_exists($key, $this->config) === true) {
            return $this->config[$key];
        }

        throw new ConfigKeyNotFoundException("Ключ конфигурации '{$key}' не найден");
    }
}
