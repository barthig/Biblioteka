<?php
declare(strict_types=1);
namespace App\Application\Command\SystemSetting;

class CreateSystemSettingCommand
{
    public function __construct(
        public readonly string $key,
        public readonly mixed $value,
        public readonly string $valueType = 'string',
        public readonly ?string $description = null
    ) {
    }
}
