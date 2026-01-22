<?php

namespace framework\storage;

interface DebugTagStorageInterface
{
    public function getTag(): string;
    public function setTag(string $tag): void;
}