<?php

namespace jujelitsa\framework\connection;

use jujelitsa\framework\configStorage\ConfigStorageInterface;

class AliasManager
{
    private array $aliasToPathMap = [];

    public function __construct(ConfigStorageInterface $configurationStorage)
    {
        $this->aliasToPathMap = $configurationStorage->get('aliases');
    }

    public function addAliasAndPath(string $alias, string $path): void
    {
        if (str_starts_with($alias, '@') === false) {
            throw new \InvalidArgumentException("Алиас должен начинаться с '@'");
        }

        if (str_contains($alias, '/') === true) {
            throw new \InvalidArgumentException("Алиас не должен содержать '/'");
        }

        if (str_starts_with($path, '@') === true) {

            $slashPos = strpos($path, '/');
            $aliasInPath = $slashPos === false ? $path : substr($path, 0, $slashPos);

            if (isset($this->aliasToPathMap[$aliasInPath]) === false) {
                throw new \InvalidArgumentException("Алиас '$aliasInPath' не найден в пути '$path'");
            }

            $this->aliasToPathMap[$alias] = str_replace($aliasInPath, $this->aliasToPathMap[$aliasInPath], $path);
            return;
        }

        $this->aliasToPathMap[$alias] = $path;
    }

    public function buildPath(string $path): string
    {
        if (str_starts_with($path, '@') === false) {
            throw new \InvalidArgumentException('Алиас не указан');
        }

        $slashPos = strpos($path, '/');
        $alias = $slashPos === false ? $path : substr($path, 0, $slashPos);

        if (isset($this->aliasToPathMap[$alias]) === false ) {
            throw new \InvalidArgumentException("Алиас '$alias' не известен");
        }

        if (is_string($this->aliasToPathMap[$alias]) === false) {
            throw new \InvalidArgumentException("Алиас '$alias' задан некорректно");
        }

        return str_replace($alias, $this->aliasToPathMap[$alias], $path);
    }
}