<?php
namespace App\Application\Handler\Command;

use App\Application\Command\StaffRole\UpdateStaffRoleCommand;
use App\Entity\StaffRole;
use App\Repository\StaffRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateStaffRoleHandler
{
    public function __construct(
        private StaffRoleRepository $staffRoleRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(UpdateStaffRoleCommand $command): StaffRole
    {
        $role = $this->staffRoleRepository->find($command->roleId);
        
        if (!$role) {
            throw new NotFoundHttpException('Staff role not found');
        }

        if ($command->name !== null) {
            $role->setName($command->name);
        }
        
        if ($command->modules !== null) {
            $role->setModules($command->modules);
        }
        
        if ($command->description !== null) {
            $role->setDescription($command->description);
        }

        $this->entityManager->flush();

        return $role;
    }
}
