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
        $secret = getenv('JWT_SECRET') ?: ($_ENV['JWT_SECRET'] ?? null);
        if (!$secret) {
            throw new \RuntimeException('JWT_SECRET not configured');
        }

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = $claims;
        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + $ttl;

        $bHeader = self::base64UrlEncode(json_encode($header));
        $bPayload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $bHeader . '.' . $bPayload, $secret, true);
        $bSig = self::base64UrlEncode($signature);

        return sprintf('%s.%s.%s', $bHeader, $bPayload, $bSig);
    }

    public static function validateToken(string $token): ?array
    {
        $secret = getenv('JWT_SECRET') ?: ($_ENV['JWT_SECRET'] ?? null);
        if (!$secret) return null;

        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        [$bHeader, $bPayload, $bSig] = $parts;

        $expectedSig = hash_hmac('sha256', $bHeader . '.' . $bPayload, $secret, true);
        $expectedB = self::base64UrlEncode($expectedSig);
        if (!hash_equals($expectedB, $bSig)) return null;

        $jsonPayload = self::base64UrlDecode($bPayload);
        $payload = json_decode($jsonPayload, true);
        if (!$payload) return null;
        if (isset($payload['exp']) && time() > (int)$payload['exp']) return null;
        return $payload;
    }
}
