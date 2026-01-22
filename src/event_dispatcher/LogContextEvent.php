<?php

namespace jujelitsa\framework\event_dispatcher;

class LogContextEvent
{
    public const ATTACH_CONTEXT = 'log.attach.context';
    public const DETACH_CONTEXT = 'log.detach.context';
    public const FLUSH_CONTEXT  = 'log.flush.context';
    public const ATTACH_EXTRAS  = 'log.attach.extras';
    public const FLUSH_EXTRAS   = 'log.flush.extras';
    public const ATTACH_CATEGORY = 'log.category.attach';
}