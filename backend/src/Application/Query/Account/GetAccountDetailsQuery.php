<?php
namespace App\Application\Query\Account;

class GetAccountDetailsQuery
{
    public function __construct(
        public readonly int $userId
    ) {
    }
}
