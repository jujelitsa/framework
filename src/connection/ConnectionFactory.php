<?php

namespace jujelitsa\framework\connection;

class ConnectionFactory implements ConnectionFactoryInterface
{
    public function createConnection(array $config): DataBaseConnectionInterface
    {
        return match ($config['driver']) {
            'mysql' => new DataBaseConnection($config),
            'file' => new JsonDataBaseConnection(...$config),
        };
    }
}
