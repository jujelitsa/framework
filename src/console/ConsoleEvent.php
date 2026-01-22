<?php

namespace jujelitsa\framework\console;

enum ConsoleEvent: string
{
    case CONSOLE_INPUT_BEFORE_PARSE = 'console.input.before.parse';

    case CONSOLE_INPUT_AFTER_PARSE = 'console.input.after.parse';
}
