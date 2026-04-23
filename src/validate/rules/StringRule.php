<?php

namespace jujelitsa\framework\validate\rules;

use jujelitsa\framework\validate\RuleInterface;

class StringRule implements RuleInterface
{
    public function validate(mixed $value): bool
    {
        return is_string($value) || is_numeric($value);
    }

    public function getErrorMessage(string $value): string
    {
        return "{$value} не является строкой";
    }
}