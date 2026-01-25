<?php
/**
 * Authentication Debug Script
 * Tests JWT token handling end-to-end
 * 
 * Usage: 
 *   curl -H "Authorization: Bearer YOUR_TOKEN" http://localhost/test-auth.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// Get Authorization header from various sources
$authHeader = null;

// Method 1: getallheaders()
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
} elseif (isset($headers['authorization'])) {
    $authHeader = $headers['authorization'];
}

// Method 2: $_SERVER
if (!$authHeader && isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
}

// Method 3: Apache specific
if (!$authHeader && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}

// Extract bearer token
$token = null;
$tokenValid = false;
$payload = null;
$error = null;

if ($authHeader && stripos($authHeader, 'Bearer ') === 0) {
    $token = substr($authHeader, 7);
    
    // Try to decode and validate token
    try {
        // Include autoloader
        require_once __DIR__ . '/../vendor/autoload.php';
        
        // Load environment variables
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    [$key, $value] = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value, '"\'');
                    if (!getenv($key)) {
                        putenv("$key=$value");
                    }
                }
            }
        }
        
        // Validate token using JwtService
        $payload = \App\Service\Auth\JwtService::validateToken($token);
        $tokenValid = $payload !== null;
        
        if (!$tokenValid) {
            $error = 'Token validation failed - invalid signature, expired, or malformed';
        }
    } catch (\Throwable $e) {
        $error = 'Exception: ' . $e->getMessage();
    }
}

// Collect debug info
$debugInfo = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'headers' => [
        'authorization_received' => $authHeader !== null,
        'authorization_value' => $authHeader ? (strlen($authHeader) > 50 ? substr($authHeader, 0, 50) . '...' : $authHeader) : null,
        'all_headers' => array_keys($headers),
    ],
    'server' => [
        'HTTP_AUTHORIZATION' => isset($_SERVER['HTTP_AUTHORIZATION']) ? 'SET' : 'NOT SET',
        'REDIRECT_HTTP_AUTHORIZATION' => isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? 'SET' : 'NOT SET',
    ],
    'token' => [
        'present' => $token !== null,
        'length' => $token ? strlen($token) : 0,
        'valid' => $tokenValid,
    ],
    'payload' => $tokenValid ? [
        'sub' => $payload['sub'] ?? null,
        'email' => $payload['email'] ?? null,
        'roles' => $payload['roles'] ?? [],
        'exp' => isset($payload['exp']) ? date('Y-m-d H:i:s', $payload['exp']) : null,
        'iat' => isset($payload['iat']) ? date('Y-m-d H:i:s', $payload['iat']) : null,
    ] : null,
    'error' => $error,
    'environment' => [
        'JWT_SECRET_configured' => (bool)(getenv('JWT_SECRET') ?: ($_ENV['JWT_SECRET'] ?? null)),
        'JWT_SECRETS_configured' => (bool)(getenv('JWT_SECRETS') ?: ($_ENV['JWT_SECRETS'] ?? null)),
    ],
];

// HTTP status based on token validation
if (!$authHeader) {
    http_response_code(200); // No auth header is OK for debug endpoint
    $debugInfo['message'] = 'No Authorization header received. Send a Bearer token to test.';
} elseif (!$tokenValid) {
    http_response_code(401);
    $debugInfo['status'] = 'error';
    $debugInfo['message'] = 'Token validation failed';
} else {
    $debugInfo['message'] = 'Token is valid!';
}

echo json_encode($debugInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
