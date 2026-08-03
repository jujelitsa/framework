<?php

namespace jujelitsa\framework\validate\rules;

use DateTime;
use jujelitsa\framework\validate\RuleInterface;

final class DateTimeRule implements RuleInterface
{
    public function validate(mixed $value, array $options = []): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value) === false) {
            return false;
        }

        $format = $options['format'] ?? 'Y-m-d H:i:s';

        $date = DateTime::createFromFormat($format, $value);

        return $date !== false && $date->format($format) === $value;
    }

    public function getErrorMessage(string $value): string
    {
        return "{$value} не является корректной датой";
    }
}