<?php

namespace jujelitsa\framework\resource;

use jujelitsa\framework\connection\DataBaseConnectionInterface;
use jujelitsa\framework\query\QueryBuilderInterface;

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

        $selected = false;

        if (isset($condition['fields']) === true) {
            $fields = $this->filterAccessibleFields($condition['fields']);
            if (empty($fields) === false) {
                $query->select($fields);
                $selected = true;
            }
        }

        if ($selected === false && empty($this->accessibleFields) === false) {
            $query->select($this->accessibleFields);
            $selected = true;
        }

        if (isset($condition['filter']) === true && is_array($condition['filter']) === true) {
            $filter = $this->filterAccessibleFilters($condition['filter']);
            if (empty($filter) === false) {
                $query->where($filter);
            }
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

    private function prepareJoins(QueryBuilderInterface $query, array $expands): void
    {
        foreach ($expands as $relationName) {
            if (isset($this->relationships[$relationName]) === false) {
                throw new \RuntimeException("Связь '{$relationName}' не настроена");
            }

            $relation = $this->relationships[$relationName];
            $table = $relation['table'] ?? $relationName;
            $on = $this->buildJoinCondition($relation);

            $query->join('LEFT', $table, $on);
        }
    }

    private function buildJoinCondition(array $relation): string
    {
        $foreignKey = $relation['foreign_key'] ?? 'id';
        $localKey = $relation['local_key'] ?? 'id';
        $foreignTable = $relation['table'] ?? $relation['resource'] ?? '';

        return $this->resourceName . '.' . $localKey . ' = ' . $foreignTable . '.' . $foreignKey;
    }

    private function mapRelationships(array $rows, array $expands): array
    {
        if (empty($expands) === true) {
            return $rows;
        }

        $result = [];
        foreach ($rows as $row) {
            $mappedRow = [];
            
            foreach ($row as $fieldName => $value) {
                if (str_contains($fieldName, '.') === false) {
                    $mappedRow[$fieldName] = $value;
                    continue;
                }

                [$relation, $key] = explode('.', $fieldName, 2);
                
                if (in_array($relation, $expands, true) === true) {
                    $mappedRow['relationships'][$relation][$key] = $value;
                } else {
                    $mappedRow[$fieldName] = $value;
                }
            }
            
            $result[] = $mappedRow;
        }
        
        return $result;
    }

    private function filterAccessibleFields(array $fields): array
    {
        if (empty($this->accessibleFields) === true) {
            return $fields;
        }

        $filtered = [];
        foreach ($fields as $field) {
            $baseField = str_contains($field, '.') === true 
                ? explode('.', $field, 2)[1] 
                : $field;
            
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
            $baseField = str_contains($field, '.') === true 
                ? explode('.', $field, 2)[1] 
                : $field;
            
            if (in_array($baseField, $this->accessibleFilters, true) === true) {
                $filtered[$field] = $criteria;
            }
        }

        return $filtered;
    }
}