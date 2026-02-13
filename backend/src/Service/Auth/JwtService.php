<?php
declare(strict_types=1);
namespace App\Service\Auth;

use App\Exception\ExternalServiceException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    /**
     * @param array<string, mixed> $claims
     */
    public static function createToken(array $claims, int $ttl = 900): string
    {
        $secrets = self::getSecrets();
        if (empty($secrets)) {
            throw new ExternalServiceException('JWT secret is not configured');
        }
        $currentSecret = $secrets[0];
        $now = time();
        $payload = array_merge($claims, [
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
            'iss' => 'biblioteka',
            'aud' => 'biblioteka-api',
            'jti' => bin2hex(random_bytes(16)),
        ]);
        return JWT::encode($payload, $currentSecret, 'HS256', '1');
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function validateToken(string $token): ?array
    {
        $secrets = self::getSecrets();
        if (empty($secrets)) {
            return null;
        }

        // Try to decode header to get kid
        $tks = explode('.', $token);
        if (count($tks) !== 3) {
            return null;
        }

        try {
            $headerJson = JWT::urlsafeB64Decode($tks[0]);
            $header = json_decode($headerJson, true);
        } catch (\Throwable) {
            return null;
        }

        $kid = $header['kid'] ?? '1';
        $secretIndex = is_numeric($kid) ? (int)$kid - 1 : 0;
        $secret = $secrets[$secretIndex] ?? $secrets[0];

        try {
            JWT::$leeway = 30; // 30 seconds clock skew tolerance
            $payload = (array) JWT::decode($token, new Key($secret, 'HS256'));
        } catch (\Throwable) {
            return null;
        }

        // Validate iss and aud
        if (!isset($payload['iss']) || $payload['iss'] !== 'biblioteka') {
            return null;
        }
        if (!isset($payload['aud']) || $payload['aud'] !== 'biblioteka-api') {
            return null;
        }

        return $payload;
    }

    /**
     * @return string[]
     */
    private static function getSecrets(): array
    {
        $secretsStr = getenv('JWT_SECRETS') ?: ($_ENV['JWT_SECRETS'] ?? null);
        if (!$secretsStr) {
            // Fallback to single secret
            $single = getenv('JWT_SECRET') ?: ($_ENV['JWT_SECRET'] ?? null);
            $single = $single ? trim($single) : '';
            return $single !== '' ? [$single] : [];
        }
        $secrets = array_map('trim', explode(',', $secretsStr));
        return array_values(array_filter($secrets, static fn(string $s) => $s !== ''));
    }
}

