<?php

namespace jujelitsa\framework\resource\enum;

enum ResourceActionTypesEnum: string
{
    case INDEX = 'index';
    case VIEW = 'view';
    case CREATE = 'create';
    case UPDATE = 'update';
    case PATCH = 'patch';
    case DELETE = 'delete';

}
