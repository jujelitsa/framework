<?php

namespace jujelitsa\framework\exception;

final class InvalidQueryException extends \Exception
{
    public function __construct(string $message = 'Произошла ошибка при выполнении запроса')
    {
        parent::__construct($message);
    }
}