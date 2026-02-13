<?php
declare(strict_types=1);
namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final class ApiSecretUser implements UserInterface
{
    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return ['ROLE_SERVICE'];
    }

    public function getUserIdentifier(): string
    {
        return 'api-secret';
    }

    public function eraseCredentials(): void
    {
    }
}
