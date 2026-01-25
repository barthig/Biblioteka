<?php
namespace App\Service;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SecurityService
{
    public function __construct(
        private ?TokenStorageInterface $tokenStorage = null
    ) {}

    /**
     * Return JWT payload attached by JwtAuthenticator or ApiAuthSubscriber.
     * @return array<string, mixed>|null
     */
    public function getJwtPayload(Request $request): ?array
    {
        $p = $request->attributes->get('jwt_payload');
        return is_array($p) ? $p : null;
    }

    /**
     * Get the authenticated user from request attributes or Symfony security token.
     */
    public function getCurrentUser(Request $request): ?User
    {
        // First try request attribute (set by JwtAuthenticator)
        $user = $request->attributes->get('jwt_user');
        if ($user instanceof User) {
            return $user;
        }

        // Fallback to Symfony security token
        if ($this->tokenStorage !== null) {
            $token = $this->tokenStorage->getToken();
            if ($token !== null) {
                $tokenUser = $token->getUser();
                if ($tokenUser instanceof User) {
                    return $tokenUser;
                }
            }
        }

        return null;
    }

    /**
     * Check whether the request identity has any of the expected roles.
     * @param string[] $roles
     */
    public function hasAnyRole(Request $request, array $roles): bool
    {
        // First try JWT payload
        $payload = $this->getJwtPayload($request);
        if ($payload && isset($payload['roles']) && is_array($payload['roles'])) {
            $granted = $payload['roles'];
            foreach ($roles as $role) {
                if (in_array($role, $granted, true)) {
                    return true;
                }
                if ($role === 'ROLE_LIBRARIAN' && in_array('ROLE_ADMIN', $granted, true)) {
                    return true;
                }
            }
            return false;
        }

        // Fallback to Symfony security token
        $user = $this->getCurrentUser($request);
        if ($user !== null) {
            $granted = $user->getRoles();
            foreach ($roles as $role) {
                if (in_array($role, $granted, true)) {
                    return true;
                }
                if ($role === 'ROLE_LIBRARIAN' && in_array('ROLE_ADMIN', $granted, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check whether the request identity has given role.
     */
    public function hasRole(Request $request, string $role): bool
    {
        return $this->hasAnyRole($request, [$role]);
    }

    /**
     * Get current user ID from JWT payload or Symfony security token.
     */
    public function getCurrentUserId(Request $request): ?int
    {
        // First try JWT payload
        $payload = $this->getJwtPayload($request);
        if ($payload && isset($payload['sub'])) {
            return (int) $payload['sub'];
        }

        // Fallback to user entity
        $user = $this->getCurrentUser($request);
        if ($user !== null) {
            return $user->getId();
        }

        return null;
    }
}
