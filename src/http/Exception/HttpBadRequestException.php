<?php

namespace framework\http\Exception;

use framework\http\Exception\HttpException;

class HttpBadRequestException extends HttpException
{
    public function __construct(string $message = 'Неправильный запрос')
    {
        parent::__construct(400, $message);
    }
}