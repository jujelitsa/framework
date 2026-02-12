<?php

namespace jujelitsa\framework\http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;

class ServerRequest extends Message implements ServerRequestInterface
{
    private string $method;
    private ?array $parsedBody = null;
    private array $attributes = [];

    public function __construct(
        string $method,
        private UriInterface $uri,
        protected StreamInterface $body,
        private array $serverParams = [],
        private array $cookieParams = [],
        private array $queryParams = [],
        private array $uploadedFiles = [],
        protected array $headers = []
    ) {
        $this->method = strtoupper($method);

        parent::__construct($body, $headers,);
    }

    public function getRequestTarget(): string
    {
        $path = $this->uri->getPath();
        $query = $this->uri->getQuery();
        return $path . ($query ? '?' . $query : '');
    }

    public function withRequestTarget(string $requestTarget): self
    {
        throw new \RuntimeException('Not implemented');
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): self
    {
        $new = clone $this;
        $new->method = strtoupper($method);
        return $new;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): self
    {
        $new = clone $this;
        $new->uri = $uri;

        if ($preserveHost === false || $this->hasHeader('Host') === false) {
            $new = $new->withHeader('Host', $uri->getHost());
        }

        return $new;
    }

    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies): self
    {
        $new = clone $this;
        $new->cookieParams = $cookies;
        return $new;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query): self
    {
        $new = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles): self
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;
        return $new;
    }

    public function getParsedBody(): ?array
    {
        if (str_contains($this->getHeaderLine('Content-Type'), 'application/x-www-form-urlencoded') === true) {
            return $this->parsedBody;
        }

        if (str_contains($this->getHeaderLine('Content-Type'), 'application/json') === true) {
            $currentPos = $this->body->tell();
            $this->body->rewind();

            $json = $this->body->getContents();

            $this->body->seek($currentPos);

            $this->parsedBody = empty($json) === false ? json_decode($json, true) : [];
            return $this->parsedBody;
        }

        return null;
    }

    public function withParsedBody($data): self
    {
        $new = clone $this;
        $new->parsedBody = $data;
        return $new;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute(string $name, $value): self
    {
        $new = clone $this;
        $new->attributes[$name] = $value;
        return $new;
    }

    public function withoutAttribute(string $name): self
    {
        $new = clone $this;
        unset($new->attributes[$name]);
        return $new;
    }
}