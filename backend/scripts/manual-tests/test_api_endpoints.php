<?php
// Test API endpoints - symuluje zapytania z frontendu

require_once __DIR__ . '/vendor/autoload.php';

// Load environment
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

$apiUrl = 'http://localhost:8000';
$apiSecret = $_ENV['API_SECRET'] ?? 'change_me_api';

echo "\n=== TESTING API ENDPOINTS ===\n\n";

function makeRequest($url, $method = 'GET', $token = null, $data = null) {
    global $apiSecret;
    
    $headers = [
        'Content-Type: application/json',
        'X-API-SECRET: ' . $apiSecret
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data && in_array($method, ['POST', 'PUT'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => $response,
        'json' => json_decode($response, true)
    ];
}

// Test 1: Health Check
echo "1. Testing /health endpoint...\n";
$result = makeRequest("$apiUrl/health");
echo "   Status: {$result['code']}\n";
echo "   Response: {$result['body']}\n";

// Test 2: Login to get token
echo "\n2. Testing /api/auth/login...\n";
$loginData = [
    'email' => 'user1@example.com',
    'password' => 'password1'  // From AppFixtures.php
];
$result = makeRequest("$apiUrl/api/auth/login", 'POST', null, $loginData);
echo "   Status: {$result['code']}\n";
if ($result['code'] === 200 && isset($result['json']['token'])) {
    echo "   ✓ Login successful\n";
    $token = $result['json']['token'];
    echo "   Token: " . substr($token, 0, 50) . "...\n";
} else {
    echo "   ✗ Login failed\n";
    echo "   Response: {$result['body']}\n";
    exit(1);
}

// Test 3: Get user profile
echo "\n3. Testing /api/auth/profile...\n";
$result = makeRequest("$apiUrl/api/auth/profile", 'GET', $token);
echo "   Status: {$result['code']}\n";
if ($result['code'] === 200) {
    echo "   ✓ Profile retrieved\n";
    echo "   User: {$result['json']['email']}\n";
} else {
    echo "   ✗ Failed to get profile\n";
    echo "   Response: {$result['body']}\n";
}

// Test 4: Get user loans
echo "\n4. Testing /api/loans (user's loans)...\n";
$result = makeRequest("$apiUrl/api/loans", 'GET', $token);
echo "   Status: {$result['code']}\n";
if ($result['code'] === 200) {
    echo "   ✓ Loans retrieved\n";
    $loans = $result['json']['data'] ?? [];
    echo "   Total loans: " . count($loans) . "\n";
    if (count($loans) > 0) {
        $loan = $loans[0];
        echo "   First loan:\n";
        echo "     ID: {$loan['id']}\n";
        echo "     Book: {$loan['book']['title']}\n";
        echo "     Due: {$loan['dueAt']}\n";
    }
} else {
    echo "   ✗ Failed to get loans\n";
    echo "   Response: {$result['body']}\n";
}

// Test 5: Get user favorites
echo "\n5. Testing /api/favorites (user's favorites)...\n";
$result = makeRequest("$apiUrl/api/favorites", 'GET', $token);
echo "   Status: {$result['code']}\n";
if ($result['code'] === 200) {
    echo "   ✓ Favorites retrieved\n";
    $favorites = $result['json']['data'] ?? [];
    echo "   Total favorites: " . count($favorites) . "\n";
    if (count($favorites) > 0) {
        $fav = $favorites[0];
        echo "   First favorite:\n";
        echo "     Book: {$fav['book']['title']}\n";
    }
} else {
    echo "   ✗ Failed to get favorites\n";
    echo "   Response: {$result['body']}\n";
}

// Test 6: Get user reservations
echo "\n6. Testing /api/reservations (user's reservations)...\n";
$result = makeRequest("$apiUrl/api/reservations", 'GET', $token);
echo "   Status: {$result['code']}\n";
if ($result['code'] === 200) {
    echo "   ✓ Reservations retrieved\n";
    $reservations = $result['json']['data'] ?? [];
    echo "   Total reservations: " . count($reservations) . "\n";
    if (count($reservations) > 0) {
        $res = $reservations[0];
        echo "   First reservation:\n";
        echo "     Book: {$res['book']['title']}\n";
        echo "     Status: {$res['status']}\n";
    }
} else {
    echo "   ✗ Failed to get reservations\n";
    echo "   Response: {$result['body']}\n";
}

// Test 7: Get public books
echo "\n7. Testing /api/books (public access)...\n";
$result = makeRequest("$apiUrl/api/books");
echo "   Status: {$result['code']}\n";
if ($result['code'] === 200) {
    echo "   ✓ Books retrieved\n";
    $books = $result['json']['data'] ?? [];
    echo "   Total books: " . count($books) . "\n";
} else {
    echo "   ✗ Failed to get books\n";
    echo "   Response: {$result['body']}\n";
}

echo "\n=== END OF API TESTS ===\n";
