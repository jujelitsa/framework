<?php

namespace jujelitsa\framework\validate\rules;

use jujelitsa\framework\validate\RuleInterface;

class RegexRule implements RuleInterface
{
    public function validate(mixed $value, array $options = []): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if ($options['pattern'] === null) {
            throw new \InvalidArgumentException('Regex rule обязательно должно содержать "pattern" параметр');
        }

        return preg_match($options['pattern'], $value) === 1;
    }

    public function getErrorMessage(mixed $value, array $options = []): string
    {
        $message = $options['message'] ?? 'Значение содержит недопустимые символы';
        return $message;
    }
}