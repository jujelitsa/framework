<?php

namespace framework\http\Exception;

use framework\http\Exception\HttpException;

class HttpNotFoundException extends HttpException
{
    public function __construct(string $message = 'Маршрут не найден')
    {
        parent::__construct(404, $message);
    }
}