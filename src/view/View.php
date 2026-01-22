<?php

namespace jujelitsa\framework\view;

use jujelitsa\framework\view\ViewRendererInterface;
use jujelitsa\framework\view\ViewNotFoundException;

class View implements ViewRendererInterface
{
    private string $baseViewsDir;

    public function __construct(string $baseViewsDir)
    {
        $this->baseViewsDir = rtrim($baseViewsDir, '/\\');

        if (is_dir($this->baseViewsDir) === false) {
            throw new \RuntimeException("Каталог представлений не найден: {$this->baseViewsDir}");
        }
    }

    public function render(string $view, array $params = []): string
    {
        $path = $this->baseViewsDir . '/' . ltrim($view, '/\\') . '.php';

        if (file_exists($path) === false) {
            throw new ViewNotFoundException($view, $path);
        }

        extract($params);

        ob_start();
        include $path;
        return ob_get_clean();
    }
}