<?php

namespace jujelitsa\framework\validate\rules;

use jujelitsa\framework\validate\RuleInterface;

class RequiredRule implements RuleInterface
{
    public function validate(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }
        
        if (is_string($value) === true && trim($value) === '') {
            return false;
        }
        
        if (is_array($value) === true && empty($value) === true) {
            return false;
        }
        
        return true;
    }

    public function getErrorMessage(string $value): string
    {
        return "Поле обязательно для заполнения";
    }
}