<?php

namespace jujelitsa\framework\query;

use jujelitsa\framework\query\StatementParametersInterface;

interface QueryBuilderInterface
{
    public function select(array|string $fields): static;

    public function from(array|string $resource): static;

    public function where(array $condition): static;

    public function whereIn(string $column, array $values): static;

    public function join(string $type, string|array $resource, string $on): static;

    public function orderBy(array $columns): static;

    public function limit(int $limit): static;

    public function offset(int $offset): static;

    public function reset(): static;

    public function getStatement(): StatementParametersInterface;
}
