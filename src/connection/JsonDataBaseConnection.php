<?php

namespace jujelitsa\framework\connection;

use jujelitsa\framework\exception\FileNotFoundException;
use jujelitsa\framework\exception\InvalidQueryException;
use jujelitsa\framework\query\QueryBuilderInterface;
use jujelitsa\framework\query\StatementParametersInterface;

class JsonDataBaseConnection implements DataBaseConnectionInterface
{
    private ?string $lastInsertId = null;

    public function __construct(
        private readonly AliasManager $aliasManager,
    ) {}

    public function select(QueryBuilderInterface $query): array
    {
        $statement = $query->getStatement();
        $data = $this->loadData($statement->resource);

        return $this->applyFilters($data, $statement);
    }

    public function selectOne(QueryBuilderInterface $query): ?array
    {
        $result = $this->select($query);
        return $result[0] ?? null;
    }

    public function selectColumn(QueryBuilderInterface $query): array
    {
        $statement = $query->getStatement();

        if (count($statement->selectFields) !== 1) {
            throw new InvalidQueryException('selectColumn() требует ровно одно поле в SELECT');
        }

        $fieldName = $statement->selectFields[0];

        $result = $this->select($query);

        return array_column($result, $fieldName);
    }

    public function selectScalar(QueryBuilderInterface $query): mixed
    {
        $result = $this->selectOne($query);

        if ($result === null) {
            return null;
        }

        return reset($result);
    }

    public function update(string $resource, array $data, array $condition): int
    {
        $filepath = $this->getFilePath($resource);
        $items = $this->loadData($resource);

        $result = $this->updateItems($items, $data, $condition);

        $this->saveData($filepath, $result['items']);

        return $result['updatedCount'];
    }

    private function updateItems(array $items, array $data, array $condition): array
    {
        $updatedCount = 0;

        foreach ($items as $key => $item) {
            if ($this->matchesCondition($item, $condition) === true) {
                $items[$key] = array_merge($item, $data);
                $updatedCount++;
            }
        }

        return [
            'items' => $items,
            'updatedCount' => $updatedCount
        ];
    }

    public function insert(string $resource, array $data): int
    {
        $filepath = $this->getFilePath($resource);
        $items = $this->loadData($resource);

        $newId = $this->getNextId($items);

        $data['id'] = $newId;
        $items[] = $data;

        $this->saveData($filepath, $items);
        $this->lastInsertId = (string)$newId;

        return $newId;
    }

    private function getNextId(array $items): int
    {
        if ($this->lastInsertId !== null) {
            return (int)$this->lastInsertId + 1;
        }

        $maxId = 0;
        foreach ($items as $item) {
            if (isset($item['id']) === true && (int)$item['id'] > $maxId) {
                $maxId = (int)$item['id'];
            }
        }

        return $maxId + 1;
    }

    public function delete(string $resource, array $condition): int
    {
        $filepath = $this->getFilePath($resource);
        $items = $this->loadData($resource);
        $originalCount = count($items);

        $filteredItems = array_filter($items, function ($item) use ($condition) {
            return $this->matchesCondition($item, $condition) === false;
        });

        $this->saveData($filepath, array_values($filteredItems));

        return $originalCount - count($filteredItems);
    }

    public function getLastInsertId(): string
    {
        return $this->lastInsertId ?? '';
    }

    private function applyFilters(array $data, StatementParametersInterface $statement): array
    {
        if (empty($statement->whereClause) === false) {
            $data = array_filter($data, function ($item) use ($statement) {
                return $this->matchesCondition($item, $statement->whereClause);
            });
        }

        if (empty($statement->orderByClause) === false) {
            $data = $this->sortData($data, $statement->orderByClause);
        }

        if ($statement->limit !== null || $statement->offset !== null) {
            $data = array_slice($data, $statement->offset ?? 0, $statement->limit);
        }

        if (empty($statement->selectFields) === false) {
            $allowedFields = array_flip($statement->selectFields);
            $data = array_map(function ($item) use ($allowedFields) {
                return array_intersect_key($item, $allowedFields);
            }, $data);
        }

        return array_values($data);
    }

    private function matchesCondition(array $item, array $condition): bool
    {
        foreach ($condition as $field => $filterValue) {
            $itemValue = $item[$field] ?? null;

            if (is_array($filterValue) === false) {
                $filterValue = [OperatorsEnum::EQ->value => $filterValue];
            }

            if ($this->matchesFieldCondition($itemValue, $filterValue) === false) {
                return false;
            }
        }

        return true;
    }

    private function matchesFieldCondition(mixed $itemValue, array $fieldConditions): bool
    {
        foreach ($fieldConditions as $operator => $compareValue) {
            if ($this->evaluateOperator($operator, $itemValue, $compareValue) === false) {
                return false;
            }
        }

        return true;
    }

    private function evaluateOperator(string $operator, mixed $item, mixed $compare): bool
    {
        return match($operator) {
            OperatorsEnum::EQ->value => (string)$item === (string)$compare,
            OperatorsEnum::NEQ->value => (string)$item !== (string)$compare,
            OperatorsEnum::GT->value => (string)$item > (string)$compare,
            OperatorsEnum::GTE->value => (string)$item >= (string)$compare,
            OperatorsEnum::LT->value => (string)$item < (string)$compare,
            OperatorsEnum::LTE->value => (string)$item <= (string)$compare,
            OperatorsEnum::IN->value => in_array($item, (array)$compare, true),
            OperatorsEnum::NIN->value => in_array($item, (array)$compare, true) === false,
            OperatorsEnum::LIKE->value => str_contains($item, $compare),
            default => throw new \InvalidArgumentException("Оператор '{$operator}' не поддерживается"),
        };
    }

    private function sortData(array $data, array $orderBy): array
    {
        usort($data, function ($a, $b) use ($orderBy) {
            foreach ($orderBy as $field => $direction) {

                if (OrderDirectionEnum::tryFrom($direction) === null) {
                    throw new InvalidQueryException('Неверное направление сортировки для поля');
                }

                $aValue = $a[$field] ?? null;
                $bValue = $b[$field] ?? null;

                if ($aValue === $bValue) {
                    continue;
                }

                $isDesc = strtolower($direction) === OrderDirectionEnum::DESC->value;

                return $isDesc
                    ? ($aValue <=> $bValue) * -1
                    : ($aValue <=> $bValue);
            }

            return 0;
        });

        return $data;
    }

    private function loadData(string $resource): array
    {
        $filepath = $this->getFilePath($resource);

        if (file_exists($filepath) === false) {
            return [];
        }

        $content = file_get_contents($filepath);

        if ($content === false) {
            return [];
        }

        $data = json_decode($content, true);

        return is_array($data) === true ? $data : [];
    }

    private function saveData(string $filepath, array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($filepath, $json);
    }

    private function getFilePath(string $resource): string
    {
        $path = $this->aliasManager->buildPath($resource . '.json');

        if (file_exists($path) === false) {
            throw new FileNotFoundException("Файл $path не существует");
        }

        return $path;
    }

    public function beginTransaction(): void
    {
        throw new InvalidQueryException('Не поддерживается для файлов');
    }

    public function commit(): void
    {
        throw new InvalidQueryException('Не поддерживается для файлов');
    }

    public function rollBack(): void
    {
        throw new InvalidQueryException('Не поддерживается для файлов');
    }

    public function getFields(string $table): array
    {
        $items = $this->loadData($table);

        if (empty($items) === true) {
            return [];
        }

        $fields = [];
        foreach ($items as $item) {
            $fields = array_merge($fields, array_keys($item));
        }

        return array_values(array_unique($fields));
    }
}