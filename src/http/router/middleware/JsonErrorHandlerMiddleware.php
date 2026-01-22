<?php

namespace jujelitsa\framework\http\router\middleware;

use jujelitsa\framework\contracts\ErrorHandlerInterface;
use jujelitsa\framework\http\enum\ContentTypeEnum;
use jujelitsa\framework\http\router\MiddlewareInterface;
use jujelitsa\framework\logger\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class JsonErrorHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ErrorHandlerInterface $errorHandler,
    ) {}

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return void
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): void
    {
        $this->errorHandler->switchContentTypeMode(ContentTypeEnum::JSON->value);
        $next($request, $response);
    }
}