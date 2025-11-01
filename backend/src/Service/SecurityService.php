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
     * Check whether the request identity has given role.
     */
    public function hasRole(Request $request, string $role): bool
    {
        $payload = $this->getJwtPayload($request);
        if ($payload && isset($payload['roles']) && is_array($payload['roles'])) {
            return in_array($role, $payload['roles'], true);
        }
        // allow if x-api-secret header matched? we don't set separate flag, so default deny
        return false;
    }
}
