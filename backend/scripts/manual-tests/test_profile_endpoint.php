<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/bootstrap.php';

use App\Kernel;

$kernel = new Kernel($_ENV['APP_ENV'], (bool)$_ENV['APP_DEBUG']);
$kernel->boot();

echo "\n=== Testing /api/auth/profile Endpoint ===\n\n";

// Login first to get token
$loginUrl = 'http://localhost:8000/api/auth/login';
$loginData = [
    'email' => 'user1@example.com',
    'password' => 'password1'
];

$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$loginResponse = curl_exec($ch);
$loginStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($loginStatus !== 200) {
    echo "[ERR] Login failed: $loginResponse\n";
    exit(1);
}

$loginJson = json_decode($loginResponse, true);
$token = $loginJson['token'] ?? null;

if (!$token) {
    echo "[ERR] No token in login response\n";
    exit(1);
}

echo "[OK] Login successful\n\n";

// Test profile endpoint
echo "Testing /api/auth/profile...\n";
$profileUrl = 'http://localhost:8000/api/auth/profile';

$ch = curl_init($profileUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$profileResponse = curl_exec($ch);
$profileStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $profileStatus\n";
echo "Response:\n";
echo json_encode(json_decode($profileResponse, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

if ($profileStatus === 200) {
    $profile = json_decode($profileResponse, true);
    if (isset($profile['id']) && isset($profile['name']) && isset($profile['roles'])) {
        echo "[OK] Profile endpoint works correctly!\n";
        echo "   User: {$profile['name']}\n";
        echo "   Roles: " . implode(', ', $profile['roles']) . "\n";
    } else {
        echo "[ERR] Profile is missing required fields\n";
    }
} else {
    echo "[ERR] Profile endpoint failed\n";
}

echo "\n";
