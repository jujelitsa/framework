<?php

namespace jujelitsa\framework\http\Exception;

use jujelitsa\framework\http\Exception\HttpException;

class HttpBadRequestException extends HttpException
{
    public function __construct(string $message = 'Неправильный запрос')
    {
        parent::__construct(400, $message);
    }
}