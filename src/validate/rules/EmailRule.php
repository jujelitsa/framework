<?php

namespace jujelitsa\framework\validate\rules;

use jujelitsa\framework\validate\RuleInterface;

class EmailRule implements RuleInterface
{
    public function validate(mixed $value, array $options = []): bool
    {
        if ($value === null) {
            return true;
        }
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    public function getErrorMessage(string $value): string
    {
        return "{$value} не является корректным email адресом";
    }
}