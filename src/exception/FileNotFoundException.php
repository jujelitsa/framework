<?php

namespace jujelitsa\framework\exception;

final class FileNotFoundException extends \Exception
{
    public function __construct(string $message = 'Файл не найден')
    {
        parent::__construct($message);
    }
}
