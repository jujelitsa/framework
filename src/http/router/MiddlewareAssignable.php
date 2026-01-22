<?php

namespace framework\http\router;

interface MiddlewareAssignable
{
    /**
     * Добавление мидлвеера
     * 
     * @param  callable|string $middleware коллбек функция или неймспейс класса мидлвеера
     * @return MiddlewareAssignable
     */
    function addMiddleware(callable|string $middleware): MiddlewareAssignable;
}
