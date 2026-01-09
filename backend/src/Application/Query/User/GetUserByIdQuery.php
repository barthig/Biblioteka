<?php

namespace App\Application\Query\User;

class GetUserByIdQuery
{
    public function __construct(
        public readonly int $userId
    ) {
    }
}
