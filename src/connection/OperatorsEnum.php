<?php

namespace jujelitsa\framework\connection;

enum OperatorsEnum: string
{
    case EQ = '$eq';
    case NEQ = '$neq';
    case GT = '$gt';
    case GTE = '$gte';
    case LT = '$lt';
    case LTE = '$lte';
    case IN = '$in';
    case NIN = '$nin';
    case LIKE = '$like';
}