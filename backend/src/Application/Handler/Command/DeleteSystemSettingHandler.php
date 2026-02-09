<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\SystemSetting\DeleteSystemSettingCommand;
use App\Repository\SystemSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class DeleteSystemSettingHandler
{
    public function __construct(
        private SystemSettingRepository $systemSettingRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(DeleteSystemSettingCommand $command): void
    {
        $setting = $this->systemSettingRepository->find($command->settingId);
        
        if (!$setting) {
            throw new NotFoundHttpException('System setting not found');
        }

        $this->entityManager->remove($setting);
        $this->entityManager->flush();
    }
}
