<?php
declare(strict_types=1);
namespace App\Application\Query\Alert;

class UserAlertsQuery
{
    public function __construct(
        public readonly int $userId
    ) {
    }
}
