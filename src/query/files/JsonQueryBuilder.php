<?php

namespace jujelitsa\framework\query\files;

use jujelitsa\framework\exception\InvalidQueryException;
use jujelitsa\framework\query\QueryBuilderInterface;
use jujelitsa\framework\connection\OperatorsEnum;
use jujelitsa\framework\query\StatementParametersInterface;

final class JsonQueryBuilder implements QueryBuilderInterface
{
    private ?string $resource = null;
    private array $selectFields = [];
    private array $whereClause = [];
    private array $orderByClause = [];
    private ?int $limit = null;
    private ?int $offset = null;

    public function select(array|string $fields): static
    {
        $this->selectFields = is_string($fields) === true ? [$fields] : $fields;
        return $this;
    }

    public function from(array|string $resource): static
    {
        if (is_array($resource) === true) {
            throw new InvalidQueryException('Метод from() для файлов принимает только строку с именем ресурса');
        }

        $this->resource = $resource;
        return $this;
    }

    public function where(array $condition): static
    {
        foreach ($condition as $field => $filterValue) {
            $filterValue = is_array($filterValue) === false
                ? [OperatorsEnum::EQ->value => $filterValue]
                : $filterValue;

            $this->whereClause[$field] = array_merge(
                $this->whereClause[$field] ?? [],
                $filterValue
            );
        }

        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        $this->whereClause[$column][OperatorsEnum::IN->value] = $values;
        return $this;
    }

    public function join(string $type, string|array $resource, string $on): static
    {
        throw new InvalidQueryException('Метод join() не поддерживается для файлов');
    }

    public function orderBy(array $columns): static
    {
        foreach ($columns as $key => $value) {
            $this->orderByClause[$key] = $value;
        }

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit < 0 ? null : $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset < 0 ? null : $offset;
        return $this;
    }

    public function getStatement(): StatementParametersInterface
    {
        if ($this->resource === null) {
            throw new InvalidQueryException('Ресурс не задан');
        }

        return new StatementParameters(
            resource: $this->resource,
            selectFields: $this->selectFields,
            whereClause: $this->whereClause,
            orderByClause: $this->orderByClause,
            limit: $this->limit,
            offset: $this->offset
        );
    }

    public function reset(): static
    {
        $this->resource = null;
        $this->selectFields = [];
        $this->whereClause = [];
        $this->orderByClause = [];
        $this->limit = null;
        $this->offset = null;

        return $this;
    }
}
