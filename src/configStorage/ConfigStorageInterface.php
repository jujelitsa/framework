<?php

namespace jujelitsa\framework\configStorage;

interface ConfigStorageInterface
{
    public function get(string $key): ?string;
}