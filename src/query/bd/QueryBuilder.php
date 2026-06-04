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

    public function reset(): static
    {
        $this->select = null;
        $this->from = null;
        $this->where = null;
        $this->joins = null;
        $this->orderBy = null;
        $this->limit = null;
        $this->offset = null;
        $this->bindings = [];

        return $this;
    }

    public function select(array|string $fields): static
    {
        $fieldsArray = is_array($fields) ? $fields : [$fields];

        $processedFields = array_map(function($field) {
            if (is_array($field) === true) {
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

    public function where(array $condition, int $level = 0): static
    {
        if ($level > 1) {
            throw new \InvalidArgumentException(
                'Max where nesting level is 2'
            );
        }

        if (
            isset($condition[0]) === true
            && is_string($condition[0]) === true
        ) {
            $logic = strtoupper($condition[0]);

            array_shift($condition);

            $parts = [];

            foreach ($condition as $item) {

                if (is_array($item) === false) {
                    continue;
                }

                $builder = new self();

                $builder->bindings = &$this->bindings;

                $builder->where(
                    $item,
                    $level + 1
                );

                $sql = $builder->getWhereExpression();

                if ($sql !== '') {
                    $parts[] = $sql;
                }
            }

            if ($parts !== []) {

                $expression =
                    '(' .
                    implode(" {$logic} ", $parts) .
                    ')';

                $this->appendWhere($expression);
            }

            return $this;
        }

        $parts = [];

        foreach ($condition as $column => $value) {

            if (is_array($value) === false) {
                $parts[] = $this->applyOperator($column, OperatorsEnum::EQ->value, $value);
                continue;
            }

            foreach ($value as $operator => $val) {
                $expression = $this->applyOperator(
                    $column,
                    $operator,
                    $val
                );

                if ($expression !== '') {
                    $parts[] = $expression;
                }
            }
        }

        if ($parts !== []) {
            $this->appendWhere(
                implode(' AND ', $parts)
            );
        }

        return $this;
    }

    private function appendWhere(string $expression): void
    {
        if ($this->where === null) {
            $this->where = 'WHERE ' . $expression;
            return;
        }

        $this->where .= ' AND ' . $expression;
    }

    public function join(string $type,
        string|array $resource,
        string $on
    ): static {
        $join =
            strtoupper($type)
            . ' JOIN '
            . $this->buildJoinTable($resource)
            . ' ON '
            . $on;

        if ($this->joins === null) {
            $this->joins = $join;
            return $this;
        }

        $this->joins .= ' ' . $join;
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
        $cleanColumn = preg_replace('/[^a-zA-Z_]/', '', str_replace('.', '_', $column));

        if ($operator === OperatorsEnum::EQ->value) {
            $param = $this->bind($cleanColumn, $value);
            return "{$column} = {$param}";
        }

        if ($operator === OperatorsEnum::NEQ->value) {
            $param = $this->bind($cleanColumn, $value);
            return "{$column} != {$param}";
        }

        if ($operator === OperatorsEnum::GT->value) {
            $param = $this->bind($cleanColumn, $value);
            return "{$column} > {$param}";
        }

        if ($operator === OperatorsEnum::GTE->value) {
            $param = $this->bind($cleanColumn, $value);
            return "{$column} >= {$param}";
        }

        if ($operator === OperatorsEnum::LT->value) {
            $param = $this->bind($cleanColumn, $value);
            return "{$column} < {$param}";
        }

        if ($operator === OperatorsEnum::LTE->value) {
            $param = $this->bind($cleanColumn, $value);
            return "{$column} <= {$param}";
        }

        if ($operator === OperatorsEnum::LIKE->value) {
            $param = $this->bind($cleanColumn, $value);
            return "{$column} LIKE {$param}";
        }

        if ($operator === OperatorsEnum::IN->value) {
            return $this->in($column, (array) $value, false);
        }

        if ($operator === OperatorsEnum::NIN->value) {
            return $this->in($column, (array) $value, true);
        }

        throw new \InvalidArgumentException(
            "Неизвестный оператор {$operator}"
        );
    }

    private function in(
        string $column,
        array $values,
        bool $not
    ): string {
        if ($values === []) {
            return $not === true
                ? '1 = 1'
                : '1 = 0';
        }

        $cleanColumn = preg_replace(
            '/[^a-zA-Z0-9_]/',
            '_',
            $column
        );

        $placeholders = [];

        foreach ($values as $i => $value) {
            $param =':' . $cleanColumn . '_' . $i . '_' . count($this->bindings);

            $this->bindings[$param] = $value;

            $placeholders[] = $param;
        }

        return sprintf(
            '%s %s (%s)',
            $column,
            $not === true ? 'NOT IN' : 'IN',
            implode(', ', $placeholders)
        );
    }

    private function bind(string $column, mixed $value): string
    {
        $cleanColumn = preg_replace('/[^a-zA-Z0-9_]/', '_', $column);
        $param = ':' . $cleanColumn . '_' . count($this->bindings);
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

    private function getWhereExpression(): string
    {
        if ($this->where === null) {
            return '';
        }

        return preg_replace(
            '/^WHERE\s+/i',
            '',
            $this->where
        );
    }

    public function getRawWhere(): string
    {
        return $this->where ?? '';
    }
}