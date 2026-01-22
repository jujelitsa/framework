<?php

namespace jujelitsa\framework\http;

use jujelitsa\framework\contracts\ErrorHandlerInterface;
use jujelitsa\framework\event_dispatcher\EventDispatcherInterface;
use jujelitsa\framework\http\contracts\HttpKernelInterface;
use jujelitsa\framework\http\enum\ContentTypeEnum;
use jujelitsa\framework\http\Exception\HttpException;
use jujelitsa\framework\http\router\HTTPRouterInterface;
use jujelitsa\framework\logger\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Ядро обработки обработки HTTP-запросов
 */
class HttpKernel implements HttpKernelInterface
{
    public function __construct(
        private readonly ResponseInterface $response,
        private readonly HTTPRouterInterface $router,
        private readonly LoggerInterface $logger,
        private readonly ErrorHandlerInterface $errorHandler,
    ) {}

    /**
     * Обработка входящего запроса
     *
     * @return ResponseInterface объект ответа
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = clone $this->response;

        try {
            $result = $this->router->dispatch($request);

            if (is_array($result) === true) {
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                $response = $response->withHeader('Content-Type', 'application/json');
            }

            if (is_array($result) === false) {
                $response->getBody()->write($result);
                $response = $response->withHeader('Content-Type', 'text/html');
            }

        } catch (HttpException $e) {
            $this->logger->error($e);
            $response = $response->withStatus($e->getStatusCode(), $e->getMessage());
            $response->getBody()->write($this->errorHandler->handle($e));

            $response = $this->applyContentTypeFromErrorHandler($response);
        } catch (\Throwable $e) {
            $this->logger->error($e);

            $response = $response->withStatus(500, $e->getMessage());

            $response->getBody()->write($this->errorHandler->handle($e));

            $response = $this->applyContentTypeFromErrorHandler($response);
        }

        return $response;
    }

    private function applyContentTypeFromErrorHandler(ResponseInterface $response): ResponseInterface
    {
        if ($this->errorHandler->isContentType(ContentTypeEnum::JSON->value)) {
            return $response->withHeader('Content-Type', 'application/json');

        }

        return $response->withHeader('Content-Type', 'text/html');
    }
}
