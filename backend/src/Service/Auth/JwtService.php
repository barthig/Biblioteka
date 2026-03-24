<?php
declare(strict_types=1);

namespace App\Service\Auth;

use App\Exception\ExternalServiceException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    private const ISSUER = 'biblioteka';
    private const AUDIENCE = 'biblioteka-api';
    private const LEEWAY_SECONDS = 30;
    private const MIN_SECRET_LENGTH = 32;

    /**
     * @param array<string, mixed> $claims
     */
    public static function createToken(array $claims, int $ttl = 900): string
    {
        $secrets = self::getSecrets();
        if (empty($secrets)) {
            throw new ExternalServiceException('JWT secret is not configured');
        }

        $currentSecret = self::assertSecretStrength($secrets[0]);
        $now = time();
        $payload = array_merge($claims, [
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
            'iss' => self::ISSUER,
            'aud' => self::AUDIENCE,
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

        $tks = explode('.', $token);
        if (count($tks) !== 3) {
            return null;
        }

        try {
            $headerJson = JWT::urlsafeB64Decode($tks[0]);
            $header = json_decode($headerJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        $candidateSecrets = self::resolveCandidateSecrets($secrets, $header['kid'] ?? null);
        $previousLeeway = JWT::$leeway;
        JWT::$leeway = self::LEEWAY_SECONDS;

        try {
            foreach ($candidateSecrets as $secret) {
                try {
                    $payload = (array) JWT::decode($token, new Key(self::assertSecretStrength($secret), 'HS256'));
                    return self::isPayloadValid($payload) ? $payload : null;
                } catch (\Throwable) {
                    continue;
                }
            }
        } finally {
            JWT::$leeway = $previousLeeway;
        }

        return null;
    }

    /**
     * @return string[]
     */
    private static function getSecrets(): array
    {
        $secretsStr = getenv('JWT_SECRETS') ?: ($_ENV['JWT_SECRETS'] ?? null);
        if (!$secretsStr) {
            $single = getenv('JWT_SECRET') ?: ($_ENV['JWT_SECRET'] ?? null);
            $single = $single ? trim($single) : '';

            return $single !== '' ? [$single] : [];
        }

        $secrets = array_map('trim', explode(',', $secretsStr));

        return array_values(array_filter($secrets, static fn(string $secret) => $secret !== ''));
    }

    /**
     * @param string[] $secrets
     * @return string[]
     */
    private static function resolveCandidateSecrets(array $secrets, mixed $kid): array
    {
        if (!is_string($kid) && !is_int($kid)) {
            return $secrets;
        }

        $secretIndex = (int) $kid - 1;
        if ($secretIndex < 0 || !isset($secrets[$secretIndex])) {
            return $secrets;
        }

        $preferredSecret = $secrets[$secretIndex];
        unset($secrets[$secretIndex]);

        return [$preferredSecret, ...array_values($secrets)];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function isPayloadValid(array $payload): bool
    {
        if (($payload['iss'] ?? null) !== self::ISSUER) {
            return false;
        }

        $audience = $payload['aud'] ?? null;
        if (is_array($audience)) {
            if (!in_array(self::AUDIENCE, $audience, true)) {
                return false;
            }
        } elseif ($audience !== self::AUDIENCE) {
            return false;
        }

        $subject = $payload['sub'] ?? null;
        if (!is_int($subject) && !is_string($subject)) {
            return false;
        }

        if (filter_var((string) $subject, FILTER_VALIDATE_INT) === false || (int) $subject <= 0) {
            return false;
        }

        return true;
    }

    private static function assertSecretStrength(string $secret): string
    {
        if (strlen($secret) < self::MIN_SECRET_LENGTH) {
            throw new ExternalServiceException('JWT secret must be at least 32 characters long');
        }

        return $secret;
    }
}