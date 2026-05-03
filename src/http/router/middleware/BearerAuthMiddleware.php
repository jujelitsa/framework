<?php
 
namespace jujelitsa\framework\http\router\middleware;
 
use jujelitsa\framework\http\router\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use jujelitsa\framework\http\Exception\HttpUnauthorizedException;
use jujelitsa\framework\http\token\TokenValidator;

class BearerAuthMiddleware implements MiddlewareInterface
{
    public function __construct(private TokenValidator $tokenValidator) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): void
    {
        $authorization = $request->getHeaderLine('Authorization');

        $hasHeader = empty($authorization) === false;
        $isBearer = $hasHeader === true && stripos($authorization, 'Bearer ') === 0;

        if ($isBearer === false) {
            throw new HttpUnauthorizedException('Missing or invalid Authorization header');
        }

        $token = substr($authorization, 7);

        try {
            $payload = $this->tokenValidator->validate($token);
        } catch (\Exception $e) {
            throw new HttpUnauthorizedException('Invalid or expired token');
        }

        $hasSubject = isset($payload['sub']) === true;

        if ($hasSubject === true) {
            $request = $request->withAttribute('subject', $payload['sub']);
        }

        $next($request, $response);
    }
}