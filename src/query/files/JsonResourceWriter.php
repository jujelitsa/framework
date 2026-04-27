<?php

namespace jujelitsa\framework\query\files;

use jujelitsa\framework\connection\DataBaseConnectionInterface;
use jujelitsa\framework\resource\ResourceWriterInterface;

class JsonResourceWriter implements ResourceWriterInterface
{
    private ?string $resourceName = null;
    private array $accessibleFields = [];

    public function __construct(
        private readonly DataBaseConnectionInterface $databaseConnection,
    ) {}

    public function setResourceName(string $name): static
    {
        $this->resourceName = $name;
        return $this;
    }

    public function create(array $values): int
    {
        $this->validateState();

        return $this->databaseConnection->insert($this->resourceName, $values);
    }

    public function update(string|int $id, array $values): int
    {
        $this->validateState();
        $allFields = $this->accessibleFields;

        $fullData = [];
        foreach ($allFields as $field) {

            if ($field === 'id') {
                continue;
            }

            $fullData[$field] = array_key_exists($field, $values)
                ? $values[$field]
                : null;
        }

        return $this->databaseConnection->update(
            $this->resourceName,
            $fullData,
            ['id' => (int)$id]
        );
    }

    public function patch(string|int $id, array $values): int
    {

        $this->validateState();

        return $this->databaseConnection->update(
            $this->resourceName,
            $values,
            ['id' => (int)$id]
        );
    }

    public function delete(string|int $id): int
    {
        $this->validateState();
        return $this->databaseConnection->delete(
            $this->resourceName,
            ['id' => $id]
        );
    }

    public function validateState(): void
    {
        if ($this->resourceName === null) {
            throw new \InvalidArgumentException('Ресурс не задан');
        }
    }

    public function setAccessibleFields(array $fieldNames): static
    {
        $this->accessibleFields = $fieldNames;
        return $this;
    }
}