<?php

namespace framework\console;

use framework\contracts\ErrorHandlerInterface;
use Throwable;

class ErrorHandler implements ErrorHandlerInterface
{

    public function handle(Throwable $e): string
    {
        $message ='';

        foreach (explode(PHP_EOL, (string) $e) as $index => $line) {

            if ($index === 0) {
                $message .= "\033[0m" . "\033[". ConsoleColors::FG_WHITE->value . ";" . ConsoleColors::BG_RED->value . "m" . PHP_EOL . PHP_EOL . " $line " . PHP_EOL . "\033[0m";
                $message .= PHP_EOL;
                continue;
            }

            if ($index === 1) {
                continue;
            }

            $message .=  PHP_EOL. $line . PHP_EOL;

        }

        return $message;
    }
}