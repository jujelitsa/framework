<?php

namespace jujelitsa\framework\contracts;

use Throwable;

interface ErrorHandlerInterface
{
    /**
     * @param  Throwable $e объект ошибки
     * @return string
     */
    public function handle(Throwable $e): string;
}