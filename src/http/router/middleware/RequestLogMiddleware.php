<?php

namespace jujelitsa\framework\http\router\middleware;

use jujelitsa\framework\http\router\MiddlewareInterface;
use jujelitsa\framework\logger\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestLogMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * @param  ServerRequestInterface $request
     * @param  ResponseInterface $response
     * @param  callable $next
     * @return void
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): void
    {
        $this->logger->debug('Выполнено обращение методом ' . $request->getMethod() . ' к энпдоинту ' . $request->getUri()->getPath());

        $next($request, $response);
    }
}