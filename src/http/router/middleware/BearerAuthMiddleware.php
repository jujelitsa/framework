<?php
 
namespace jujelitsa\framework\http\router\middleware;
 
use jujelitsa\framework\http\router\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use jujelitsa\framework\http\Exception\HttpUnauthorizedException;
use jujelitsa\framework\http\token\TokenValidator;
use jujelitsa\framework\resource\interface\UserRepositoryInterface;
use Ramsey\Uuid\Uuid;

class BearerAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TokenValidator $tokenValidator,
        private UserRepositoryInterface $userRepository
    ) {}

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
        } catch (\Throwable $e) {
            throw new HttpUnauthorizedException('Invalid or expired token');
        }

        if (isset($payload['exp']) === false || $payload['exp'] <= time()) {
            throw new HttpUnauthorizedException('Token has expired');
        }

        if (isset($payload['sub']) === false || empty($payload['sub']) || Uuid::isValid($payload['sub']) === false) {
            throw new HttpUnauthorizedException('Invalid token: missing user identifier');
        }
        
        $user = $this->userRepository->findByUuid($payload['sub']);
        
        if ($user === null) {
            throw new HttpUnauthorizedException('User not found');
        }

        $hasSubject = isset($payload['sub']) === true;

        if ($hasSubject === true) {
            $request = $request->withAttribute('subject', $payload['sub']);
        }

        $next($request, $response);
    }
}