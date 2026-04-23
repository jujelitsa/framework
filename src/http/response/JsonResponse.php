<?php

namespace jujelitsa\framework\http\response;

class JsonResponse extends Response
{
    public function __construct(mixed $data = null, int $status = 200)
    {
        $body = null;
        if ($data !== null) {
            $body = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        
        parent::__construct($status, $body, 'application/json');
    }
}