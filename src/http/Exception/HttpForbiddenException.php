<?php

namespace jujelitsa\framework\http\Exception;

use jujelitsa\framework\http\Exception\HttpException;

class HttpForbiddenException extends HttpException
{
    public function __construct(string $message = 'Доступ запрещён')
    {
        parent::__construct(403, $message);
    }
}