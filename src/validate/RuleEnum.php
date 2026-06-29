<?php

namespace jujelitsa\framework\validate;

enum RuleEnum: string
{
    case INTEGER = 'integer';
    case FLOAT = 'float';
    case STRING = 'string';
    case BOOLEAN = 'boolean';
    case REQUIRED = 'required';
    case UNIQUE = 'unique';
    case MAXLENSTR = 'maxLenStr';
    case MINLENSTR = 'minLenStr';
    case REGEX = 'regex';
    case EMAIL = 'email';
    case EXISTS = 'exists';
}