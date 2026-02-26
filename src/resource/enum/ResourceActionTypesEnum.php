<?php

namespace jujelitsa\framework\resource\enum;

enum ResourceActionTypesEnum: string
{
    case CREATE = 'create';
    case PATCH = 'patch';
    case UPDATE = 'update';
}
