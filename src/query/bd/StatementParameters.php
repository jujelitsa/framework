<?php

namespace jujelitsa\framework\query\bd;

use jujelitsa\framework\query\StatementParametersInterface;

final readonly class StatementParameters implements StatementParametersInterface
{
    public function __construct(
        public string $sql,
        public array $bindings
    ) {}
}
