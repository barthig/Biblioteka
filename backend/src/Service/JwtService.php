<?php
namespace App\Service;

class JwtService
{
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $pad = 4 - (strlen($data) % 4);
        if ($pad < 4) $data .= str_repeat('=', $pad);
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public static function createToken(array $claims, int $ttl = 3600): string
    {
        $secrets = self::getSecrets();
        if (empty($secrets)) {
            error_log('JWT secret is not configured');
            throw new \RuntimeException('JWT secret is not configured');
        }

        $currentSecret = $secrets[0]; // use first as current
        $kid = '1'; // key id

        $header = ['alg' => 'HS256', 'typ' => 'JWT', 'kid' => $kid];
        $payload = $claims;
        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + $ttl;
        $payload['iss'] = 'biblioteka'; // issuer
        $payload['aud'] = 'biblioteka-api'; // audience

        $bHeader = self::base64UrlEncode(json_encode($header));
        $bPayload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $bHeader . '.' . $bPayload, $currentSecret, true);
        $bSig = self::base64UrlEncode($signature);

        return sprintf('%s.%s.%s', $bHeader, $bPayload, $bSig);
    }

    public static function validateToken(string $token): ?array
    {
        $secrets = self::getSecrets();
        if (empty($secrets)) return null;

        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        [$bHeader, $bPayload, $bSig] = $parts;

        $header = json_decode(self::base64UrlDecode($bHeader), true);
        if (!$header || !isset($header['kid'])) return null;
        $kid = $header['kid'];

        // Use secret based on kid (1-based index)
        $secretIndex = is_numeric($kid) ? (int)$kid - 1 : 0;
        $secret = isset($secrets[$secretIndex]) ? $secrets[$secretIndex] : $secrets[0];

        $expectedSig = hash_hmac('sha256', $bHeader . '.' . $bPayload, $secret, true);
        $expectedB = self::base64UrlEncode($expectedSig);
        if (!hash_equals($expectedB, $bSig)) return null;

        $jsonPayload = self::base64UrlDecode($bPayload);
        $payload = json_decode($jsonPayload, true);
        if (!$payload) return null;
        if (isset($payload['exp']) && time() > (int)$payload['exp']) return null;

        // Validate iss and aud
        if (!isset($payload['iss']) || $payload['iss'] !== 'biblioteka') return null;
        if (!isset($payload['aud']) || $payload['aud'] !== 'biblioteka-api') return null;

        return $payload;
    }

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
