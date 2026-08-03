<?php

namespace jujelitsa\framework\validate\rules;

use jujelitsa\framework\validate\RuleInterface;

final class MaxLenStrRule implements RuleInterface
{
    private string $errorContext = '';

    public function validate(mixed $value, array $options = []): bool
    {
        if (isset($options['max']) === false) {
            $this->errorContext = 'Параметр max не указан';
            return false;
        }

        if ($value === null) {
            return true;
        }

        if (is_string($value) === false) {
            $this->errorContext = 'Значение должно быть строкой';
            return false;
        }

        $length = mb_strlen($value);
        if ($length > (int) $options['max']) {
            $this->errorContext = "Длина строки ({$length}) превышает максимально допустимую ({$options['max']})";
            return false;
        }

        return true;
    }

    public function getErrorMessage(mixed $value, array $options = []): string
    {
        if ($this->errorContext !== '') {
            return $this->errorContext;
        }

        return "Длина строки не должна превышать {$options['max']} символов";
    }
}