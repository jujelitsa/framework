<?php

namespace jujelitsa\framework\query\bd;

use jujelitsa\framework\connection\DataBaseConnectionInterface;
use jujelitsa\framework\query\QueryBuilderInterface;
use jujelitsa\framework\query\ResourceDataFilterInterface;

final class DataBaseResourceDataFilter implements ResourceDataFilterInterface
{
    private string $resourceName = '';
    private array $accessibleFields = [];
    private array $accessibleFilters = [];
    private array $relationships = [];

    public function __construct(
        private DataBaseConnectionInterface $db,
        private QueryBuilderInterface $queryBuilder
    ) {}

    public function setResourceName(string $name): static
    {
        $this->resourceName = $name;
        return $this;
    }

    public function setAccessibleFields(array $fieldNames): static
    {
        $this->accessibleFields = $fieldNames;
        return $this;
    }

    public function setAccessibleFilters(array $filterNames): static
    {
        $this->accessibleFilters = $filterNames;
        return $this;
    }

    public function setRelationships(array $relationships): static
    {
        $this->relationships = $relationships;
        return $this;
    }

    public function filterAll(array $condition): array
    {
        $query = $this->buildQuery($condition);

        if ($query === null) {
            return [];
        }

        if (empty($condition) === true) {
            $query->orderBy([$this->resourceName . '.id' => 'ASC']);
        }

        $rows = $this->db->select($query);

        return $this->mapRelationships($rows, $condition['expand'] ?? []);
    }

    public function filterOne(array $condition): array|null
    {
        $query = $this->buildQuery($condition);

        if ($query === null) {
            return null;
        }

        $row = $this->db->selectOne($query);

        if ($row === null) {
            return null;
        }

        $result = $this->mapRelationships([$row], $condition['expand'] ?? []);

        return $result[0] ?? null;
    }

    private function buildQuery(array $condition): ?QueryBuilderInterface
    {
        if (empty($this->resourceName) === true) {
            throw new \RuntimeException('Имя ресурса не задано');
        }

        $query = clone $this->queryBuilder;
        $query->from($this->resourceName);

        $selectFields = $this->buildSelectFields($condition);
        if (empty($selectFields) === false) {
            $query->select($selectFields);
        }

        $whereConditions = $this->buildWhereConditions($condition);
        if (empty($whereConditions) === false) {
            $query->where($whereConditions);
        }

        if (isset($condition['expand']) === true && is_array($condition['expand']) === true) {
            $this->prepareJoins($query, $condition['expand']);
        }

        if (isset($condition['limit']) === true && $condition['limit'] !== null) {
            $limit = filter_var($condition['limit'], FILTER_VALIDATE_INT);
            if ($limit !== false && $limit > 0) {
                $query->limit($limit);
            }
        }

        if (isset($condition['offset']) === true && $condition['offset'] !== null) {
            $offset = filter_var($condition['offset'], FILTER_VALIDATE_INT);
            if ($offset !== false && $offset >= 0) {
                $query->offset($offset);
            }
        }

        return $query;
    }

    private function buildWhereConditions(array $condition): array
    {
        $whereConditions = [];

        $hasFilter = isset($condition['filter']) === true && is_array($condition['filter']) === true;
        if ($hasFilter === false) {
            return $whereConditions;
        }

        $filter = $this->filterAccessibleFilters($condition['filter']);
        if (empty($filter) === true) {
            return $whereConditions;
        }

        foreach ($filter as $field => $value) {
            $cleanField = trim($field);
            $hasDot = str_contains($cleanField, '.') === true;
            if ($hasDot === true) {
                $parts = explode('.', $cleanField);
                $cleanField = end($parts);
            }
            $whereConditions[$this->resourceName . '.' . $cleanField] = $value;
        }

        return $whereConditions;
    }

    private function buildSelectFields(array $condition): array
    {
        $selectFields = [];

        $hasFields = isset($condition['fields']);
        if ($hasFields === true) {
            $fields = $this->filterAccessibleFields($condition['fields']);
            $selectFields = $this->addFieldsToSelect($fields, $selectFields);
        }

        $hasNoFields = $hasFields === false;
        if ($hasNoFields === true && empty($this->accessibleFields) === false) {
            $selectFields = $this->addFieldsToSelect($this->accessibleFields, $selectFields);
        }

        $hasExpand = isset($condition['expand']) === true && is_array($condition['expand']) === true;
        if ($hasExpand === true) {
            $selectFields = $this->addExpandToSelect($condition['expand'], $selectFields);
        }

        return $selectFields;
    }

    private function addFieldsToSelect(array $fields, array $selectFields): array
    {
        foreach ($fields as $field) {
            $selectFields[] = $this->resourceName . '.' . $field;
        }
        return $selectFields;
    }

    private function addExpandToSelect(array $expands, array $selectFields): array
    {
        foreach ($expands as $relationName) {
            if (isset($this->relationships[$relationName]) === false) {
                continue;
            }

            $relation = $this->relationships[$relationName];
            $table = $relation['table'] ?? $relationName;
            $relationFields = $relation['fields'] ?? ['*'];

            $selectFields = $this->addRelationFieldsToSelect($relationFields, $table, $relationName, $selectFields);
        }

        return $selectFields;
    }

    private function addRelationFieldsToSelect(array $relationFields, string $table, string $relationName, array $selectFields): array
    {
        foreach ($relationFields as $field) {
            if ($field === '*') {
                $selectFields[] = $table . '.*';
                continue;
            }

            $cleanField = $this->cleanFieldName($field);
            $alias = $relationName . '_' . $cleanField;
            $selectFields[] = $table . '.' . $cleanField . ' AS ' . $alias;
        }

        return $selectFields;
    }

    private function cleanFieldName(string $field): string
    {
        $cleanField = trim($field);

        $hasAs = stripos($cleanField, ' as ') !== false;
        if ($hasAs === true) {
            $parts = preg_split('/\s+as\s+/i', $cleanField);
            $cleanField = trim($parts[0]);
        }

        $hasDot = str_contains($cleanField, '.') === true;
        if ($hasDot === true) {
            $parts = explode('.', $cleanField);
            $cleanField = end($parts);
        }

        return $cleanField;
    }

    private function prepareJoins(QueryBuilderInterface $query, array $expands): void
    {
        foreach ($expands as $relationName) {
            if (isset($this->relationships[$relationName]) === false) {
                throw new \RuntimeException("Связь '{$relationName}' не настроена");
            }

            $relation = $this->relationships[$relationName];
            $table = $relation['table'] ?? $relationName;
            $on = $this->buildJoinCondition($relation, $relationName);

            $query->join('LEFT', $table, $on);
        }
    }

    private function buildJoinCondition(array $relation, string $relationName): string
    {
        $foreignKey = $relation['foreign_key'] ?? 'id';
        $localKey = $relation['local_key'] ?? 'id';
        $foreignTable = $relation['table'] ?? $relationName;
        $type = $relation['type'] ?? 'belongsTo';

        if ($type === 'belongsTo' || $type === 'belongs-to') {
            return $this->resourceName . '.' . $foreignKey . ' = ' . $foreignTable . '.' . $localKey;
        }

        return $foreignTable . '.' . $foreignKey . ' = ' . $this->resourceName . '.' . $localKey;
    }

    private function mapRelationships(array $rows, array $expands): array
    {
        if (empty($expands) === true) {
            return $rows;
        }

        $result = [];

        foreach ($rows as $row) {
            $mappedRow = $this->extractMainFields($row, $expands);
            $relationships = $this->extractRelationFields($row, $expands);

            foreach ($relationships as $relationName => $relationData) {
                $mappedRow[$relationName] = $relationData;
            }

            $result[] = $mappedRow;
        }

        return $result;
    }

    private function extractMainFields(array $row, array $expands): array
    {
        $mappedRow = [];

        foreach ($row as $fieldName => $value) {
            $isRelationField = $this->isRelationField($fieldName, $expands);

            if ($isRelationField === true) {
                continue;
            }

            $cleanFieldName = $fieldName;
            if (str_contains($fieldName, '.') === true) {
                $parts = explode('.', $fieldName);
                $cleanFieldName = end($parts);
            }

            $mappedRow[$cleanFieldName] = $value;
        }

        return $mappedRow;
    }

    private function extractRelationFields(array $row, array $expands): array
    {
        $relationships = [];

        foreach ($row as $fieldName => $value) {
            $relationName = $this->getRelationName($fieldName, $expands);

            if ($relationName === null) {
                continue;
            }

            $relatedField = substr($fieldName, strlen($relationName) + 1);

            if (isset($relationships[$relationName]) === false) {
                $relationships[$relationName] = [];
            }

            $relationships[$relationName][$relatedField] = $value;
        }

        return $relationships;
    }

    private function isRelationField(string $fieldName, array $expands): bool
    {
        foreach ($expands as $relationName) {
            if (str_starts_with($fieldName, $relationName . '_') === true) {
                return true;
            }
        }

        return false;
    }

    private function getRelationName(string $fieldName, array $expands): ?string
    {
        foreach ($expands as $relationName) {
            if (str_starts_with($fieldName, $relationName . '_') === true) {
                return $relationName;
            }
        }

        return null;
    }

    private function filterAccessibleFields(array $fields): array
    {
        if (empty($this->accessibleFields) === true) {
            return $fields;
        }

        $filtered = [];

        foreach ($fields as $field) {
            $baseField = $field;
            if (str_contains($field, '.')) {
                $parts = explode('.', $field, 2);
                $baseField = $parts[1];
            }

            if (in_array($baseField, $this->accessibleFields, true) === true) {
                $filtered[] = $field;
            }
        }

        return $filtered;
    }

    private function filterAccessibleFilters(array $filter): array
    {
        if (empty($this->accessibleFilters) === true) {
            return $filter;
        }

        $filtered = [];

        foreach ($filter as $field => $criteria) {
            $cleanField = $field;
            if (str_contains($cleanField, '.')) {
                $parts = explode('.', $cleanField);
                $cleanField = end($parts);
            }

            if (in_array($cleanField, $this->accessibleFilters, true) === true) {
                $filtered[$cleanField] = $criteria;
            }
        }

        return $filtered;
    }
}
