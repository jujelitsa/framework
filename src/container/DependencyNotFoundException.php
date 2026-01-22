<?php 

namespace jujelitsa\framework\container;

use Psr\Container\NotFoundExceptionInterface;

class DependencyNotFoundException extends \RuntimeException implements NotFoundExceptionInterface
{
    private int $statusCode = 404;

    public function __construct(string $id)
    {
        $message = sprintf('Зависимость "%s" не была найдена и не могла быть построена.', $id);
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}