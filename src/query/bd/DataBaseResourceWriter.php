<?php

namespace jujelitsa\framework\query\bd;

use jujelitsa\framework\query\ResourceWriterInterface;
use jujelitsa\framework\connection\DataBaseConnectionInterface;

final class DataBaseResourceWriter implements ResourceWriterInterface
{
    private array $accessibleFields = [];

    public function __construct(
        private DataBaseConnectionInterface $db,
        private string $resource = ''
    ) {}

    public function setResourceName(string $name): static
    {
        $this->resource = $name;
        return $this;
    }

    public function setAccessibleFields(array $fieldNames): static
    {
        $this->accessibleFields = $fieldNames;
        return $this;
    }

    public function create(array $values): int
    {
        return $this->db->insert($this->resource, $values);
    }


    public function update(string|int $id, array $values): int
    {
        if (empty($this->accessibleFields) === true) {
            throw new \RuntimeException('Нет доступных полей для изменений');
        }
        
        $fullData = [];
        foreach ($this->accessibleFields as $column) {
            if ($column === 'id') {
                continue;
            }
            
            $fullData[$column] = $values[$column] ?? null;
        }
        
        return $this->db->update($this->resource, $fullData, ['id' => $id]);
    }

    public function patch(string|int $id, array $values): int
    {
        return $this->db->update($this->resource, $values, ['id' => $id]);
    }

    public function delete(string|int $id): int
    {
        return $this->db->delete($this->resource, ['id' => $id]);
    }
}