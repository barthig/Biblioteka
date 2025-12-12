<?php
namespace App\Application\Command\SystemSetting;

class DeleteSystemSettingCommand
{
    public function __construct(
        public readonly int $settingId
    ) {
    }
}
