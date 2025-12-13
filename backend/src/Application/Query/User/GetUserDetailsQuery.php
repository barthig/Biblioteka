<?php

namespace App\Application\Query\User;

class GetUserDetailsQuery
{
    public function __construct(
        public readonly int $userId
    ) {
    }
}
