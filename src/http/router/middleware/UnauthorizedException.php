<?php

namespace jujelitsa\framework\http\router\middleware;

use jujelitsa\framework\http\Exception\HttpException;

class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Авторизация не пройдена')
    {
        parent::__construct(401, $message);
    }
}