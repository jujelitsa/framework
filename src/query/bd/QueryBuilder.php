<?php

namespace jujelitsa\framework\query\bd;

use jujelitsa\framework\query\QueryBuilderInterface;
use jujelitsa\framework\query\StatementParametersInterface;
use jujelitsa\framework\connection\OperatorsEnum;

final class QueryBuilder implements QueryBuilderInterface
{
    private ?string $select = null;
    private ?string $from = null;
    private ?string $where = null;
    private ?string $joins = null;
    private ?string $orderBy = null;
    private ?string $limit = null;
    private ?string $offset = null;
    private array $bindings = [];

    public function select(array|string $fields): static
    {
        $fieldsArray = is_array($fields) ? $fields : [$fields];
    
        $processedFields = array_map(function($field) {
            if (is_array($field)) {
                $alias = array_key_first($field);
                $column = $field[$alias];
                return $column . ' AS ' . $alias;
            }
            
            return preg_replace('/\s+as\s+/i', ' AS ', $field);
        }, $fieldsArray);
        
        $this->select = 'SELECT ' . implode(', ', $processedFields);
        return $this;
    }

    public function from(array|string $resource): static
    {
        if (is_array($resource) === false) {
            $resource = preg_replace('/\s+as\s+/i', ' AS ', $resource);
            $this->from = "FROM {$resource}";
            return $this;
        }
        
        if (count($resource) === 1) {
            $alias = array_key_first($resource);
            $table = $resource[$alias];
            $this->from = "FROM {$table} AS {$alias}";
            return $this;
        }
        
        $tables = [];
        foreach ($resource as $alias => $table) {
            if (is_int($alias) === true) {
                $tables[] = $table;
                continue;
            }
            
            $tables[] = "{$table} AS {$alias}";
        }
        
        $this->from = 'FROM ' . implode(', ', $tables);
        return $this;
    }

    public function where(array $condition): static
    {
        $whereConditions = [];
        foreach ($condition as $column => $value) {

            if (is_array($value) === false) {
                $whereConditions[] = $this->applyOperator($column, OperatorsEnum::EQ->value, $value);
                continue;
            }

            foreach ($value as $operator => $val) {
                $whereConditions[] = $this->applyOperator($column, $operator, $val);
            }
        }

        if (empty($whereConditions) === false) {
            $this->where = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        return $this;
    }

    public function whereIn(string $column, array $values, bool $not = false): static
    {
        $placeholders = [];

        foreach ($values as $i => $value) {
            $param = ':' . $column . '_in_' . $i . '_' . count($this->bindings);

            $placeholders[] = $param;
            $this->bindings[$param] = $value;
        }

        $operator = $not ? 'NOT IN' : 'IN';

        $condition = sprintf(
            '%s %s (%s)',
            $column,
            $operator,
            implode(', ', $placeholders)
        );

        if ($this->where === null) {
            $this->where = 'WHERE ' . $condition;
        } else {
            $this->where .= ' AND ' . $condition;
        }

        return $this;
    }

    public function join(string $type, string|array $resource, string $on): static
    {
        $joinTable = $this->buildJoinTable($resource);
        $joinString = strtoupper($type) . " JOIN {$joinTable} ON {$on}";
        
        if ($this->joins === null) {
            $this->joins = $joinString;
            return $this;
        }
        
        $this->joins .= ' ' . $joinString;
        return $this;
    }

    public function orderBy(array $columns): static
    {
        $orderParts = [];
        
        foreach ($columns as $column => $direction) {
            $orderParts[] = $column . ' ' . strtoupper($direction);
        }
        
        $this->orderBy = 'ORDER BY ' . implode(', ', $orderParts);
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = "LIMIT {$limit}";
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = "OFFSET {$offset}";
        return $this;
    }

    public function getStatement(): StatementParametersInterface
    {
        $parts = [
            $this->select ?? 'SELECT *',
            $this->from,
            $this->joins,
            $this->where,
            $this->orderBy,
            $this->limit,
            $this->offset,
        ];
        
        $sql = implode(' ', array_filter($parts, fn($part) => $part !== null && $part !== ''));
        
        return new StatementParameters($sql, $this->bindings);
    }

    private function applyOperator(string $column, string $operator, mixed $value): string
    {
        if ($operator === OperatorsEnum::EQ->value) {
            $param = $this->bind($column, $value);
            return "{$column} = {$param}";
        }

        if ($operator === OperatorsEnum::NEQ->value) {
            $param = $this->bind($column, $value);
            return "{$column} != {$param}";
        }

        if ($operator === OperatorsEnum::GT->value) {
            $param = $this->bind($column, $value);
            return "{$column} > {$param}";
        }

        if ($operator === OperatorsEnum::GTE->value) {
            $param = $this->bind($column, $value);
            return "{$column} >= {$param}";
        }

        if ($operator === OperatorsEnum::LT->value) {
            $param = $this->bind($column, $value);
            return "{$column} < {$param}";
        }

        if ($operator === OperatorsEnum::LTE->value) {
            $param = $this->bind($column, $value);
            return "{$column} <= {$param}";
        }

        if ($operator === OperatorsEnum::LIKE->value) {
            $param = $this->bind($column, $value);
            return "{$column} LIKE {$param}";
        }

        if ($operator === OperatorsEnum::IN->value) {
            $this->whereIn($column, (array)$value);
            return '';
        }

        if ($operator === OperatorsEnum::NIN->value) {
            $this->whereIn($column, (array)$value, true);
            return '';
        }

        throw new \InvalidArgumentException("Неизвестный оператор {$operator}");
    }

    private function bind(string $column, mixed $value): string
    {
        $param = ':' . $column . count($this->bindings);
        $this->bindings[$param] = $value;

        return $param;
    }

    private function buildJoinTable(string|array $resource): string
    {
        if (is_string($resource) === true) {
            return preg_replace('/\s+as\s+/i', ' AS ', $resource);
        }
        
        if (count($resource) === 1) {
            $alias = array_key_first($resource);
            $table = $resource[$alias];
            return "{$table} AS {$alias}";
        }
        
        $tables = [];
        foreach ($resource as $alias => $table) {
            if (is_int($alias) === true) {
                $tables[] = $table;
                continue;
            }
            
            $tables[] = "{$table} AS {$alias}";
        }
        
        return implode(', ', $tables);
    }
}