<?php
declare(strict_types=1);
namespace App\Application\Query\SystemSetting;

class ListSystemSettingsQuery
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $limit = 50
    ) {
    }
}
