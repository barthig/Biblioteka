<?php

namespace App\GraphQL\Resolver;

use App\Entity\User;
use Symfony\Component\Security\Core\Security;

class UserResolver
{
    public function __construct(
        private Security $security
    ) {
    }

    /**
     * Get current authenticated user
     */
    public function getCurrentUser(): ?array
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            return null;
        }

        return $this->userToArray($user);
    }

    /**
     * Convert User entity to array for GraphQL
     */
    private function userToArray(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified(),
            'createdAt' => $user->getCreatedAt()?->format('c'),
        ];
    }
}
