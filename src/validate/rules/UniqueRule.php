<?php

namespace jujelitsa\framework\validate\rules;

use jujelitsa\framework\validate\RuleInterface;
use jujelitsa\framework\connection\DataBaseConnectionInterface;
use jujelitsa\framework\query\QueryBuilderInterface;

class UniqueRule implements RuleInterface
{
    private string $errorContext = '';
    
    public function __construct(
        private readonly DataBaseConnectionInterface $connection,
        private readonly QueryBuilderInterface $query,
    ) {}

    public function validate(mixed $value, array $options = []): bool
    {
        $resource = $options['resource'] ?? $options['table'] ?? null;
        $target = $options['target'] ?? $options['column'] ?? $options['columns'] ?? null;
        
        if ($resource === null) {
            $this->errorContext = 'Ресурс не указан';
            return false;
        }
        
        $values = is_array($value) === true ? $value : [$value];
        $targets = is_array($target) === true ? $target : [$target];
        
        $this->query->reset();
        $this->query->select($options['target'])->from($resource);
        
        $conditions = [];
        $errorValues = [];
        
        foreach ($targets as $index => $column) {
            $val = $values[$column] ?? ($values[$index] ?? null);
            if ($val !== null) {
                $conditions[$column] = $val;
                $errorValues[] = "{$column}: {$val}";
            }
        }
        
        if (empty($conditions) === true) {
            return true;
        }
        
        $this->query->where($conditions);
        
        $count = $this->connection->selectScalar($this->query);
        
        if ($count > 0) {
            $this->errorContext = "Значение (" . implode(', ', $errorValues) . ") уже существует в таблице {$resource}";
            return false;
        }
        
        return true;
    }
    
    public function getErrorMessage(string $value): string
    {
        return $this->errorContext !== '' ? $this->errorContext : "Значение уже существует";
    }
}