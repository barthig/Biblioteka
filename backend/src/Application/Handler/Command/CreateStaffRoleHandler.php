<?php
namespace App\Application\Handler\Command;

use App\Application\Command\StaffRole\CreateStaffRoleCommand;
use App\Entity\StaffRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateStaffRoleHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(CreateStaffRoleCommand $command): StaffRole
    {
        $role = new StaffRole();
        $role->setName($command->name);
        $role->setRoleKey($command->roleKey);
        
        if (!empty($command->modules)) {
            $role->setModules($command->modules);
        }
        
        if ($command->description !== null) {
            $role->setDescription($command->description);
        }

        $this->entityManager->persist($role);
        $this->entityManager->flush();

        return $role;
    }
}
