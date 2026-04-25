<?php

namespace jujelitsa\framework\http\response;

use jujelitsa\framework\http\Response;
use GuzzleHttp\Psr7\Stream;

class JsonResponse extends Response
{
    public function __construct(mixed $data = null, int $status = 200, array $headers = [])
    {
        $content = $data !== null ? json_encode($data, JSON_UNESCAPED_UNICODE) : '';
        
        $stream = new Stream(fopen('php://temp', 'r+'));
        $stream->write($content);
        
        $headers['Content-Type'] = ['application/json'];
        
        parent::__construct($status, '', $headers, $stream);
    }
}