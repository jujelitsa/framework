<?php

namespace framework\http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\MessageInterface;
use GuzzleHttp\Psr7\Stream;

abstract class Message implements MessageInterface
{
    public function __construct(
        protected StreamInterface $body,
        protected array $headers = [],
        protected string $protocolVersion = '1.1',
    ) {}

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): self
    {
        $new = clone $this;
        $new->protocolVersion = $version;
        return $new;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    public function getHeader(string $name): array
    {
        return array_key_exists($name, $this->headers) === true ? [$this->headers[$name]] : [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): self
    {
        $new = clone $this;
        $new->headers[strtolower($name)] = is_array($value) ? $value : [$value];
        return $new;
    }

    public function withAddedHeader(string $name, $value): self
    {
        $new = clone $this;
        $new->headers[strtolower($name)] = array_merge(
            $this->getHeader($name),
            is_array($value) ? $value : [$value]
        );
        return $new;
    }

    public function withoutHeader(string $name): self
    {
        $new = clone $this;
        unset($new->headers[strtolower($name)]);
        return $new;
    }

    public function getBody(): StreamInterface
    {
        if ($this->body === null) {
            $this->body = new Stream(fopen('php://temp', 'r+'));
        }
        return $this->body;
    }

    public function withBody(StreamInterface $body): self
    {
        $new = clone $this;
        $new->body = $body;
        return $new;
    }
}