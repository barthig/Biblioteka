<?php
namespace App\Application\Query\Alert;

class UserAlertsQuery
{
    public function __construct(
        public readonly int $userId
    ) {
    }
}
