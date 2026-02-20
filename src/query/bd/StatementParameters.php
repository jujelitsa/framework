<?php

namespace jujelitsa\framework\query\bd;


final readonly class StatementParameters //extends StatementParametersInterface
{
    public function __construct(
        public string $sql,
        public array $bindings
    ) {}
}
