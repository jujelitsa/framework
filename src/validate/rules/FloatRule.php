<?php

namespace jujelitsa\framework\validate\rules;

use jujelitsa\framework\validate\RuleInterface;

class FloatRule implements RuleInterface
{
    public function validate(mixed $value, array $options = []): bool
    {
        if ($value === null) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }

    public function getErrorMessage(string $value): string
    {
        return "{$value} не является float";
    }
}