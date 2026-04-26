<?php

namespace jujelitsa\framework\validate\rules;

use jujelitsa\framework\validate\RuleInterface;

class IntegerRule implements RuleInterface
{
    public function validate(mixed $value, array $options = []): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    public function getErrorMessage(string $value): string
    {
        return "{$value} не является integer";
    }
}