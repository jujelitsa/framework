<?php

namespace jujelitsa\framework\resource;

enum ResourceEvent: string
{
    case BEFORE_LIST = 'resource.before.list';
    case AFTER_LIST = 'resource.after.list';

    case BEFORE_VIEW = 'resource.before.view';
    case AFTER_VIEW = 'resource.after.view';
    
    case BEFORE_CREATE = 'resource.before.create';
    case AFTER_CREATE = 'resource.after.create';
    
    case BEFORE_UPDATE = 'resource.before.update';
    case AFTER_UPDATE = 'resource.after.update';
    
    case BEFORE_PATCH = 'resource.before.patch';
    case AFTER_PATCH = 'resource.after.patch';
    
    case BEFORE_DELETE = 'resource.before.delete';
    case AFTER_DELETE = 'resource.after.delete';
    
    case NOT_FOUND = 'resource.not.found';
    case VALIDATION_ERROR = 'resource.validation.error';
}