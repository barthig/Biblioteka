<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\StaffRole\DeleteStaffRoleCommand;
use App\Repository\StaffRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteStaffRoleHandler
{
    public function __construct(
        private StaffRoleRepository $staffRoleRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(DeleteStaffRoleCommand $command): void
    {
        $role = $this->staffRoleRepository->find($command->roleId);
        
        if (!$role) {
            throw new NotFoundHttpException('Staff role not found');
        }

        $this->entityManager->remove($role);
        $this->entityManager->flush();
    }
}
