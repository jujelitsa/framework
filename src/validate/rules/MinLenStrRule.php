<?php

namespace jujelitsa\framework\validate\rules;

use jujelitsa\framework\validate\RuleInterface;

final class MinLenStrRule implements RuleInterface
{
    private string $errorContext = '';

    public function validate(mixed $value, array $options = []): bool
    {
        if (isset($options['min']) === false) {
            $this->errorContext = 'Параметр min не указан';
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
        if ($length < (int) $options['min']) {
            $this->errorContext = "Длина строки ({$length}) меньше минимально допустимой ({$options['min']})";
            return false;
        }

        return true;
    }

    public function getErrorMessage(mixed $value, array $options = []): string
    {
        if ($this->errorContext !== '') {
            return $this->errorContext;
        }

        return "Длина строки должна быть не менее {$options['min']} символов";
    }
}
