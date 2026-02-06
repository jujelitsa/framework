<?php

namespace jujelitsa\framework\storage;

interface ConfigStorageInterface
{
    public function get(string $key): ?string;
}