<?php
namespace App\Application\Query\IntegrationConfig;

class ListIntegrationConfigsQuery
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $limit = 50
    ) {
    }
}
