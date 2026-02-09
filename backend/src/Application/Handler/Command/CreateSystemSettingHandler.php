<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\SystemSetting\CreateSystemSettingCommand;
use App\Entity\SystemSetting;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateSystemSettingHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(CreateSystemSettingCommand $command): SystemSetting
    {
        $setting = new SystemSetting();
        $setting->setKey($command->key);
        $setting->setValueType($command->valueType);
        $setting->setValueFromMixed($command->value);
        
        if ($command->description !== null) {
            $setting->setDescription($command->description);
        }

        $this->entityManager->persist($setting);
        $this->entityManager->flush();

        return $setting;
    }
}
