<?php

namespace jujelitsa\framework\http\response;

use jujelitsa\framework\http\response\JsonResponse;

class DeleteResponse extends JsonResponse
{
    public function __construct()
    {
        parent::__construct(null, 204);
    }
}