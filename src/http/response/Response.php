<?php

namespace jujelitsa\framework\http\response;

class Response
{
    public function __construct(
        public int $statusCode = 200,
        public ?string $responseBody = null,
        public string $contentType = 'text/html; charset=utf-8',
    ) {}

    public function toArray(): array
    {
        return [
            'statusCode' => $this->statusCode,
            'body' => $this->responseBody,
            'contentType' => $this->contentType,
        ];
    }

    public function isJson(): bool
    {
        return str_contains($this->contentType, 'application/json');
    }

    public function getBody(): string
    {
        return $this->responseBody ?? '';
    }
}