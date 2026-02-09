<?php
declare(strict_types=1);
namespace App\Application\Query\SystemSetting;

class GetSystemSettingQuery
{
    public function __construct(
        public readonly int $settingId
    ) {
    }
}
