<?php
declare(strict_types=1);

namespace App\Application\Handler\Command;

use App\Application\Command\User\UpdateUserRolesCommand;
use App\Entity\User;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\StaffRoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateUserRolesHandler
{
    private const ALLOWED_BASE_ROLES = ['ROLE_USER', 'ROLE_LIBRARIAN', 'ROLE_ADMIN', 'ROLE_SYSTEM'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly StaffRoleRepository $staffRoles
    ) {
    }

    public function __invoke(UpdateUserRolesCommand $command): User
    {
        $user = $this->userRepository->find($command->userId);
        if (!$user) {
            throw NotFoundException::forUser($command->userId);
        }

        $roles = $this->normalizeRoles($command->roles);
        if ($roles === []) {
            throw ValidationException::forField('roles', 'roles are required');
        }

        $invalidRoles = [];
        foreach ($roles as $role) {
            if (in_array($role, self::ALLOWED_BASE_ROLES, true)) {
                continue;
            }
            if ($this->staffRoles->findOneByRoleKey($role) !== null) {
                continue;
            }
            $invalidRoles[] = $role;
        }

        if ($invalidRoles !== []) {
            throw ValidationException::fromErrors(['invalidRoles' => $invalidRoles]);
        }

        $user->setRoles($roles);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /** @param string[] $roles */
    private function normalizeRoles(array $roles): array
    {
        $normalized = array_map(static function (mixed $role): ?string {
            $value = strtoupper(trim((string) $role));
            if ($value === '') {
                return null;
            }

            return str_starts_with($value, 'ROLE_') ? $value : 'ROLE_' . $value;
        }, $roles);

        return array_values(array_unique(array_filter($normalized)));
    }
}
