<?php

namespace jujelitsa\framework\debugTagStorage;

interface DebugTagStorageInterface
{
    public function getTag(): string;
    public function setTag(string $tag): void;
}