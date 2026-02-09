<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\SystemSetting\UpdateSystemSettingCommand;
use App\Entity\SystemSetting;
use App\Repository\SystemSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class UpdateSystemSettingHandler
{
    public function __construct(
        private SystemSettingRepository $systemSettingRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(UpdateSystemSettingCommand $command): SystemSetting
    {
        $setting = $this->systemSettingRepository->find($command->settingId);
        
        if (!$setting) {
            throw new NotFoundHttpException('System setting not found');
        }

        if ($command->value !== null) {
            $setting->setValueFromMixed($command->value);
        }
        
        if ($command->description !== null) {
            $setting->setDescription($command->description);
        }

        if ($command->valueType !== null) {
            $setting->setValueType($command->valueType);
        }

        $this->entityManager->flush();

        return $setting;
    }
}
