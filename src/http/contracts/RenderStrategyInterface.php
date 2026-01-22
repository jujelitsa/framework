<?php

namespace jujelitsa\framework\http\contracts;

interface RenderStrategyInterface {
    public function render(\Throwable $exception): string;
}