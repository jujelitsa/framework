<?php

namespace jujelitsa\framework\validate\rules;

use jujelitsa\framework\validate\RuleInterface;
use jujelitsa\framework\connection\DataBaseConnectionInterface;
use jujelitsa\framework\query\QueryBuilderInterface;

class ExistsRule implements RuleInterface
{
    private string $errorContext = '';

    public function __construct(
        private readonly DataBaseConnectionInterface $connection,
        private readonly QueryBuilderInterface $query,
    ) {}

    public function validate(mixed $value, array $options = []): bool
    {
        $resource = $options['resource'] ?? $options['table'] ?? null;
        $target = $options['target'] ?? $options['column'] ?? null;

        if ($resource === null || $target === null) {
            $this->errorContext = 'Не указаны таблица или поле для проверки существования';
            return false;
        }

        if (is_array($value) === true) {
            $value = reset($value);
        }

        $this->query->reset();
        $this->query->select(['count' => 'COUNT(*)'])->from($resource)->where([$target => $value]);

        $count = $this->connection->selectScalar($this->query);

        if ($count > 0) {
            return true;
        }

        $this->errorContext = "Значение '{$value}' не найдено в таблице {$resource} (поле {$target})";
        return false;
    }

    public function getErrorMessage(string $value): string
    {
        return empty($this->errorContext) === false ? $this->errorContext : "Значение не существует";
    }
}