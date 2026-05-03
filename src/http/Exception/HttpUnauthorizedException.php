<?php

namespace jujelitsa\framework\http\Exception;
 
use jujelitsa\framework\http\Exception\HttpException;
 
class HttpUnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Unautorized')
    {
        parent::__construct(401, $message);
    }
}