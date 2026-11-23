<?php

namespace jujelitsa\framework\resource\cache;

interface ResourceCacheRepositoryInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl): void;
    public function delete(string $key): void;
    public function has(string $key): bool;
}