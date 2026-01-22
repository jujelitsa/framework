<?php

namespace framework\view;

class ViewNotFoundException extends \RuntimeException
{
    public function __construct(string $view, string $baseDir)
    {
        $message = sprintf(
            "View не найден: '%s'. Поиск в каталоге: '%s'",
            $view,
            rtrim($baseDir, '/\\')
        );
        parent::__construct($message);
    }
}