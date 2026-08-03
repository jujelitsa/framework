<?php

namespace jujelitsa\framework\resource\interface;

interface UserRepositoryInterface
{
    public function findByUuid(string $uuid): ?array;
}