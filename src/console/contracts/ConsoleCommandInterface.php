<?php

namespace jujelitsa\framework\console\contracts;

interface ConsoleCommandInterface
{
    public static function getDescription(): string;

    public static function getSignature(): string;

    public function execute(): void;
}