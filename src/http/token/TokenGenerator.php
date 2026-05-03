<?php

namespace jujelitsa\framework\http\token;

use Firebase\JWT\JWT;

class TokenGenerator
{
    public function __construct(private string $privateKeyPath) {}

    public function generate(array $data, int $ttl = 86400, string $algorithm = 'RS256'): string
    {
        $issuedAt = time();
        $data['iat'] = $issuedAt;
        $data['exp'] = $issuedAt + $ttl;

        $privateKey = file_get_contents($this->privateKeyPath);

        return JWT::encode($data, $privateKey, $algorithm);
    }
}