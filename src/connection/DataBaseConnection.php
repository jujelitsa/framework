<?php

namespace jujelitsa\framework\connection;

use PDO;
use PDOStatement;
use jujelitsa\framework\query\QueryBuilderInterface;
use jujelitsa\framework\connection\OperatorsEnum;

final class DataBaseConnection implements DataBaseConnectionInterface
{
    private PDO $connection;
    private string $lastInsertId = '';

    public function __construct(array $config)
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'] ?? 3306,
            $config['dbname'],
            $config['charset'] ?? 'utf8mb4'
        );

        $this->connection = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    public function select(QueryBuilderInterface $query): array
    {
        $statement = $this->execute($query);
        return $statement->fetchAll();
    }

    public function selectOne(QueryBuilderInterface $query): ?array
    {
        $statement = $this->execute($query);
        $result = $statement->fetch();

        return $result === false ? null : $result;
    }

    public function selectColumn(QueryBuilderInterface $query): array
    {
        $statement = $this->execute($query);
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    public function selectScalar(QueryBuilderInterface $query): mixed
    {
        $statement = $this->execute($query);
        return $statement->fetchColumn();
    }

    public function insert(string $resource, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ':' . $col, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $resource,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $statement = $this->connection->prepare($sql);

        foreach ($data as $key => $value) {
            $this->bind($statement, ':' . $key, $value);
        }

        $statement->execute();

        $this->lastInsertId = $this->connection->lastInsertId();

        return (int)$this->lastInsertId;
    }

    public function update(string $resource, array $data, array $condition): int
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :set_{$key}";
        }

        $where = [];
        $bindings = [];

        foreach ($condition as $column => $value) {
            $this->buildWhereCondition($where, $bindings, $column, $value);
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $resource,
            implode(', ', $set),
            implode(' AND ', $where)
        );

        $statement = $this->connection->prepare($sql);

        foreach ($data as $key => $value) {
            $this->bind($statement, ':set_' . $key, $value);
        }

        foreach ($bindings as $key => $value) {
            $this->bind($statement, $key, $value);
        }

        $statement->execute();

        return $statement->rowCount();
    }

    public function delete(string $resource, array $condition): int
    {
        $where = [];

        foreach ($condition as $key => $value) {
            $where[] = "{$key} = :{$key}";
        }

        $sql = sprintf(
            'DELETE FROM %s WHERE %s',
            $resource,
            implode(' AND ', $where)
        );

        $statement = $this->connection->prepare($sql);

        foreach ($condition as $key => $value) {
            $this->bind($statement, ':' . $key, $value);
        }

        $statement->execute();

        return $statement->rowCount();
    }

    public function getLastInsertId(): string
    {
        return $this->lastInsertId;
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollBack(): void
    {
        $this->connection->rollBack();
    }

    private function execute(QueryBuilderInterface $query): PDOStatement
    {
        
        $statementData = $query->getStatement();
        $statement = $this->connection->prepare($statementData->sql);

        foreach ($statementData->bindings as $key => $value) {
            $this->bind($statement, $key, $value);
        }

        $statement->execute();

        return $statement;
    }

    public function getFields(string $table): array
    {
        $sql = "SHOW COLUMNS FROM `{$table}`";
        $statement = $this->connection->prepare($sql);
        $statement->execute();
        
        $columns = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }
        
        return $columns;
    }

    private function bind(PDOStatement $statement, string $key, mixed $value): void
    {
        $type = match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            is_null($value) => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };

        $statement->bindValue($key, $value, $type);
    }

    private function buildWhereCondition(array &$where, array &$bindings, string $column, mixed $value): void 
    {
        if (is_array($value) === false) {
            $param = $this->createParam($bindings, $column, $value);
            $where[] = "{$column} = {$param}";
            return;
        }

        foreach ($value as $operator => $val) {
            if ($operator === OperatorsEnum::EQ->value) {
                $param = $this->createParam($bindings, $column, $val);
                $where[] = "{$column} = {$param}";
            }

            if ($operator === OperatorsEnum::NEQ->value) {
                $param = $this->createParam($bindings, $column, $val);
                $where[] = "{$column} != {$param}";
            }

            if ($operator === OperatorsEnum::GT->value) {
                $param = $this->createParam($bindings, $column, $val);
                $where[] = "{$column} > {$param}";
            }

            if ($operator === OperatorsEnum::GTE->value) {
                $param = $this->createParam($bindings, $column, $val);
                $where[] = "{$column} >= {$param}";
            }

            if ($operator === OperatorsEnum::LT->value) {
                $param = $this->createParam($bindings, $column, $val);
                $where[] = "{$column} < {$param}";
            }

            if ($operator === OperatorsEnum::LTE->value) {
                $param = $this->createParam($bindings, $column, $val);
                $where[] = "{$column} <= {$param}";
            }

            if ($operator === OperatorsEnum::LIKE->value) {
                $param = $this->createParam($bindings, $column, $val);
                $where[] = "{$column} LIKE {$param}";
            }

            if ($operator === OperatorsEnum::IN->value) {
                $placeholders = [];

                foreach ($val as $i => $item) {
                    $placeholders[] = $this->createParam(
                        $bindings,
                        $column . '_in_' . $i,
                        $item
                    );
                }

                $where[] = sprintf(
                    '%s IN (%s)',
                    $column,
                    implode(', ', $placeholders)
                );
            }

            if ($operator === OperatorsEnum::NIN->value) {
                $placeholders = [];

                foreach ($val as $i => $item) {
                    $placeholders[] = $this->createParam(
                        $bindings,
                        $column . '_nin_' . $i,
                        $item
                    );
                }

                $where[] = sprintf(
                    '%s NOT IN (%s)',
                    $column,
                    implode(', ', $placeholders)
                );
            }
        }
    }

    private function createParam(array &$bindings, string $column, mixed $value): string
    {
        $param = ':where_' . $column . '_' . count($bindings);
        $bindings[$param] = $value;

        return $param;
    }
}