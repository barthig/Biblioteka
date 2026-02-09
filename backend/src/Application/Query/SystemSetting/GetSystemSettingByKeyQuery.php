<?php
declare(strict_types=1);
namespace App\Application\Query\SystemSetting;

class GetSystemSettingByKeyQuery
{
    public function __construct(
        public readonly string $key
    ) {
    }
}
