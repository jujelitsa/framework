<?php

namespace framework\http\router\middleware;

use framework\http\router\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HTTPBasicMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly string $serverClientId,
        private readonly string $serverClientSecret,
    ) {}

    /**
     * @throws UnauthorizedException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): void
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if ($authHeader === '' || stripos($authHeader, 'Basic ') !== 0) {
            throw new UnauthorizedException();
        }

        $credentials = base64_decode(substr($authHeader, 6));
        [$requestClientId, $requestClientSecret] = explode(':', $credentials, 2);

        if ($this->serverClientId !== $requestClientId || $this->serverClientSecret !== $requestClientSecret) {
            throw new UnauthorizedException();
        }

        $next($request, $response);
    }
}