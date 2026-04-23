<?php

namespace jujelitsa\framework\validate;

interface RuleInterface
{
    public function validate(mixed $value): bool;
    public function getErrorMessage(string $value): string;
}