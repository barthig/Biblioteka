<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

class SecurityService
{
    /**
     * Return JWT payload attached by ApiAuthSubscriber, if any.
     * @return array|null
     */
    public function getJwtPayload(Request $request): ?array
    {
        $p = $request->attributes->get('jwt_payload');
        return is_array($p) ? $p : null;
    }

    /**
     * Check whether the request identity has any of the expected roles.
     */
    public function hasAnyRole(Request $request, array $roles): bool
    {
        $payload = $this->getJwtPayload($request);
        if (!$payload || !isset($payload['roles']) || !is_array($payload['roles'])) {
            return false;
        }

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

    /**
     * Check whether the request identity has given role.
     */
    public function hasRole(Request $request, string $role): bool
    {
        return $this->hasAnyRole($request, [$role]);
    }
}
