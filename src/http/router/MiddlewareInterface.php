<?php

namespace jujelitsa\framework\http\router;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

interface MiddlewareInterface
{
    /**
     * @param  ServerRequestInterface $request
     * @param  ResponseInterface $response
     * @param  callable $next
     * @return void
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): void;
}
