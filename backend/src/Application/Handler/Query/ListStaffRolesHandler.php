<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\StaffRole\ListStaffRolesQuery;
use App\Repository\StaffRoleRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListStaffRolesHandler
{
    public function __construct(
        private StaffRoleRepository $staffRoleRepository
    ) {
    }

    public function __invoke(ListStaffRolesQuery $query): array
    {
        $offset = ($query->page - 1) * $query->limit;
        
        return $this->staffRoleRepository->findBy(
            [],
            ['name' => 'ASC'],
            $query->limit,
            $offset
        );
    }
}
