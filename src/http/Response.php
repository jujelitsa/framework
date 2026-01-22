<?php

namespace jujelitsa\framework\http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Stream;

class Response extends Message implements ResponseInterface
{
    private int $statusCode;
    private string $reasonPhrase;

    public function __construct(
        int $status = 200,
        string $reasonPhrase = '',
        array $headers = [],
        ?StreamInterface $body = null,
        string $version = '1.1'
    ) {
        $this->body = $body ?? new Stream(fopen('php://input', 'r+'));
        parent::__construct($this->body, $headers, $version);
        $this->statusCode = $status;
        $this->reasonPhrase = $reasonPhrase ?: $this->getDefaultReasonPhrase($status);
        $this->protocolVersion = $version;

        foreach ($headers as $name => $value) {
            $this->headers[strtolower($name)] = is_array($value) ? $value : [$value];
        }
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): self
    {
        $new = clone $this;
        $new->statusCode = $code;
        $new->reasonPhrase = $reasonPhrase ?: $this->getDefaultReasonPhrase($code);
        return $new;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    private function getDefaultReasonPhrase(int $status): string
    {
        $phrases = [
            200 => 'OK',
            404 => 'Not Found',
            500 => 'Internal Server Error',
        ];
        return $phrases[$status] ?? '';
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("$name: " . implode('; ', $value));
        }

        echo $this->body;
    }
}