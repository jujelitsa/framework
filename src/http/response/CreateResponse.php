<?php

namespace jujelitsa\framework\http\response;

use jujelitsa\framework\http\response\JsonResponse;

class CreateResponse extends JsonResponse
{
    public function __construct(?string $body = null)
    {
        parent::__construct($body, 201);
    }
}