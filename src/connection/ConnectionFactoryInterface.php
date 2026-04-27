<?php

namespace jujelitsa\framework\connection;

interface ConnectionFactoryInterface
{
    public function createConnection(array $config): DataBaseConnectionInterface;
}
