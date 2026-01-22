<?php

namespace framework\http\router\middleware;

use framework\contracts\ErrorHandlerInterface;
use framework\http\enum\ContentTypeEnum;
use framework\http\router\MiddlewareInterface;
use framework\logger\LoggerInterface;
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