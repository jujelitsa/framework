<?php

namespace jujelitsa\framework\http\router\middleware;

use jujelitsa\framework\http\router\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class XApiKeyMidlleware implements MiddlewareInterface
{
    public function __construct(
        private readonly string $xApiKey,
    ) {}

    /**
     * @inheritDoc
     * @throws UnauthorizedException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): void
    {
        $apiKey = $request->getHeader('X-Api-Key');

        if (empty($apiKey) === true) {
            throw new UnauthorizedException('Отсутствует заголовок X-API-KEY');
        }

        if ($apiKey[0] ?? null === $this->xApiKey) {
            $next($request, $response);
            return;
        }

        throw new UnauthorizedException();
    }
}