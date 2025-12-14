<?php
namespace App\Application\Command\SystemSetting;

class UpdateSystemSettingCommand
{
    public function __construct(
        public readonly int $settingId,
        public readonly mixed $value = null,
        public readonly ?string $description = null,
        public readonly ?string $valueType = null
    ) {
    }
}
