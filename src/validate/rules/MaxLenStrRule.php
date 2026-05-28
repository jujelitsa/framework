<?php

namespace jujelitsa\framework\validate\rules;

use jujelitsa\framework\validate\RuleInterface;

class MaxLenStrRule implements RuleInterface
{
    public function validate(mixed $value, array $options = []): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value) === false) {
            return false;
        }

        return mb_strlen($value) <=  $options['max'];
    }

    public function getErrorMessage(mixed $value, array $options = []): string
    {
        return 'Длина строки не должна превышать ' .  $options['max'] . ' символов';
    }
}