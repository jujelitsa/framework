<?php

namespace jujelitsa\framework\query\files;

use jujelitsa\framework\query\StatementParametersInterface;

final readonly class StatementParameters extends StatementParametersInterface
{
    public function __construct(
        public string $resource,
        public array $selectFields = [],
        public array $whereClause = [],
        public array $orderByClause = [],
        public ?int $limit = null,
        public ?int $offset = null,
    ) {}
}
