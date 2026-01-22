<?php

namespace jujelitsa\framework;

enum EnvironmentModeEnum: string
{
    case DEVELOPMENT = 'development';

    case PRODUCTION = 'production';
}