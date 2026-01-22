<?php

namespace framework;

enum ApplicationTypeEnum: string
{
    case WEB = 'web';
    case CLI = 'cli';
}