<?php

namespace jujelitsa\framework\validate\rules;

use jujelitsa\framework\validate\RuleInterface;

class BooleanRule implements RuleInterface
{
    public function validate(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null;
    }

    public function getErrorMessage(string $value): string
    {
        return "{$value} не является булевым значением";
    }
}