<?php

namespace jujelitsa\framework\http\Exception;

use jujelitsa\framework\http\Exception\HttpException;

class HttpNotFoundException extends HttpException
{
    public function __construct(string $message = 'Маршрут не найден')
    {
        parent::__construct(404, $message);
    }
}