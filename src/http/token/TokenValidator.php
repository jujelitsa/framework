<?php

namespace jujelitsa\framework\http\token;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TokenValidator
{
    private string $publicKey;
    private string $algorithm;

    public function __construct(string $publicKeyPath, string $algorithm = 'RS256')
    {
        $this->publicKey = file_get_contents($publicKeyPath);
        $this->algorithm = $algorithm;
    }

    public function validate(string $token): array
    {
        $key = new Key($this->publicKey, $this->algorithm);
        return (array) JWT::decode($token, $key);
    }
}