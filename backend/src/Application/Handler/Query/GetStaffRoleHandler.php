<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\StaffRole\GetStaffRoleQuery;
use App\Entity\StaffRole;
use App\Repository\StaffRoleRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetStaffRoleHandler
{
    public function __construct(
        private StaffRoleRepository $staffRoleRepository
    ) {
    }

    public function __invoke(GetStaffRoleQuery $query): StaffRole
    {
        $role = $this->staffRoleRepository->find($query->roleId);
        
        if (!$role) {
            throw new NotFoundHttpException('Staff role not found');
        }

        return $role;
    }
}
