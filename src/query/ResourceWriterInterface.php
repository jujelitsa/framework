<?php

namespace jujelitsa\framework\query;

interface ResourceWriterInterface
{
    public function setResourceName(string $name): static;

    public function setAccessibleFields(array $fieldNames): static;

    public function create(array $values): int;

    public function update(string|int $id, array $values): int;

    public function patch(string|int $id, array $values): int;

    public function delete(string|int $id): int;
}
