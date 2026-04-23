<?php

namespace jujelitsa\framework\resource\formRequest;

interface FormRequestFactoryInterface
{
    public function create(string $formClassName, array $rules = []): FormRequestInterface;
}