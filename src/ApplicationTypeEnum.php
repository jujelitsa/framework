<?php

namespace jujelitsa\framework;

enum ApplicationTypeEnum: string
{
    case WEB = 'web';
    case CLI = 'cli';
}