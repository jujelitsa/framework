<?php

namespace jujelitsa\framework\resource\formRequest;

interface FormRequestFactoryInterface
{
    public function create(string $formClassName): FormRequestInterface;
}