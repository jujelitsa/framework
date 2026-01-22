<?php

namespace jujelitsa\framework\http\errorHandler\strategy;

use jujelitsa\framework\http\Exception\HttpException;

class RenderStrategyNotFoundException extends HttpException
{
    public function __construct(string $message = 'Стратегия рендеринга не найдена.')
    {
        parent::__construct(400, $message);
    }
}