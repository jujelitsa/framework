<?php

namespace jujelitsa\framework\configStorage;

final class ConfigKeyNotFoundException extends \Exception
{
    public function __construct(string $message = 'Значение по ключу не найдено')
    {
        parent::__construct($message);
    }
}
