<?php

namespace jujelitsa\framework\query\files;


use jujelitsa\framework\connection\DataBaseConnectionInterface;
use jujelitsa\framework\query\QueryBuilderInterface;
use jujelitsa\framework\resource\ResourceDataFilterInterface;

class JsonResourceDataFilter implements ResourceDataFilterInterface
{
    private ?string $resourceName = null;
    private ?array $accessibleFields = null;
    private ?array $accessibleFilters = null;

    public function __construct(
        private readonly DataBaseConnectionInterface $databaseConnection,
        private readonly QueryBuilderInterface $queryBuilder,
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

    /**
     * Возврат коллекции ресурсов, отфильтрованных в соответствие с условиями
     *
     * @param array $condition
     * Пример:
     * [
     *     "fields" => [
     *         "id",
     *         "order_id",
     *         "name",
     *     ],
     *     "filter" => [
     *         "order_id" => [
     *             "$eq" => 3,
     *         ],
     *     ],
     * ]
     * @return array
     * Пример:
     * [
     *     [
     *         "id" => 1,
     *         "order_id" => 3,
     *         "name" => "Некоторое имя 1"
     *     ],
     *     [
     *         "id" => 2,
     *         "order_id" => 3,
     *         "name" => "Некоторое имя 2"
     *     ],
     * ]
     */
    function filterAll(array $condition): array
    {
        $this->checkAccessible($condition);

        return $this->databaseConnection->select($this->buildQuery($condition));
    }

    /**
     * Возврат ресурса, отфильтрованного в соответствие с условиями
     *
     * @param array $condition
     * Пример:
     * [
     *     "fields" => [
     *         "id",
     *         "name",
     *     ],
     *     "filter" => [
     *         "id" => [
     *             "$eq" => 1,
     *         ],
     *     ],
     * ]
     * @return array|null
     * Пример:
     * [
     *     "id" => 1,
     *     "name" => "Некоторое имя 1"
     * ],
     */
    function filterOne(array $condition): array|null
    {
        $this->checkAccessible($condition);

        return $this->databaseConnection->selectOne($this->buildQuery($condition));
    }

    private function checkAccessible(array $condition): void
    {
        if (isset($condition['fields']) === true) {
            $this->checkFieldsAccessible($condition['fields']);
        }

        if (isset($condition['filter']) === true) {
            $this->checkFiltersAccessible(array_keys($condition['filter']));
        }
    }

    private function checkFieldsAccessible(array $fields): void
    {
        foreach ($fields as $field) {
            if (in_array($field, $this->accessibleFields, true) === false) {
                throw new \InvalidArgumentException("Выбранное поле недоступно для выборки");
            }
        }
    }

    private function checkFiltersAccessible(array $fields): void
    {
        foreach ($fields as $field) {
            if (in_array($field, $this->accessibleFilters, true) === false) {
                throw new \InvalidArgumentException("Выбранное поле недоступно для фильтрации");
            }
        }
    }

    private function buildQuery(array $condition): QueryBuilderInterface
    {
        $this->queryBuilder->select(empty($condition['fields']) === false ? $condition['fields'] : $this->accessibleFields);
        $this->queryBuilder->from($this->resourceName);
        $this->queryBuilder->where($condition['filter'] ?? []);
        $this->queryBuilder->orderBy($condition['order'] ?? []);

        if (isset($condition['limit']) === true)  {
            $this->queryBuilder->limit((int)$condition['limit']);
        }

        if (isset($condition['offset']) === true)  {
            $this->queryBuilder->limit((int)$condition['offset']);
        }

        return $this->queryBuilder;
    }

    public function setRelationships(array $relationships): static
    {
        return $this;
    }
}