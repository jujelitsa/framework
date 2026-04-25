<?php

namespace jujelitsa\framework\validate\rules;

use jujelitsa\framework\validate\RuleInterface;
use jujelitsa\framework\connection\DataBaseConnectionInterface;
use jujelitsa\framework\query\QueryBuilderInterface;

class UniqueRule implements RuleInterface
{
    public function __construct(
        private readonly DataBaseConnectionInterface $connection,
        private readonly QueryBuilderInterface $query,
    ) {}

    public function validate(mixed $value, array $options = []): bool
    {
        $resource = $options['resource'] ?? $options['table'] ?? null;
        $target = $options['target'] ?? $options['column'] ?? $options['columns'] ?? null;
        
        if ($resource === null) {
            return false;
        }
        
        $values = is_array($value) === true ? $value : [$value];
        $targets = is_array($target) === true ? $target : [$target];
        
        $this->query->reset();
        $this->query->select('COUNT(*)')->from($resource);
        
        $conditions = [];
        foreach ($targets as $index => $column) {
            $val = $values[$column] ?? ($values[$index] ?? null);
            if ($val !== null) {
                $conditions[$column] = $val;
            }
        }
        
        if (empty($conditions) === true) {
            return true;
        }
        
        $this->query->where($conditions);
        
        $count = $this->connection->selectScalar($this->query);
        
        if ($count > 0) {
            return false;
        }
        
        return true;
    }
    
    public function getErrorMessage(string $value): string
    {
        return "Значение уже существует";
    }
}