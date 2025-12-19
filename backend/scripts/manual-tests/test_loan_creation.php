<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/bootstrap.php';

use App\Kernel;

$kernel = new Kernel($_ENV['APP_ENV'], (bool)$_ENV['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

echo "\n=== Testing Loan Creation ===\n\n";

// Test data
$testUser = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => 'user1@example.com']);
$testBook = $em->getRepository(\App\Entity\Book::class)->findOneBy([]);

if (!$testUser) {
    echo "❌ Test user not found\n";
    exit(1);
}

if (!$testBook) {
    echo "❌ Test book not found\n";
    exit(1);
}

echo "User: {$testUser->getEmail()} (ID: {$testUser->getId()})\n";
echo "Book: {$testBook->getTitle()} (ID: {$testBook->getId()})\n";
echo "Available copies: {$testBook->getCopies()}\n";
echo "Total copies: {$testBook->getTotalCopies()}\n\n";

// Check available book copies
$bookCopies = $em->getRepository(\App\Entity\BookCopy::class)->findBy(['book' => $testBook]);
echo "Book copies in database:\n";
foreach ($bookCopies as $copy) {
    echo "  - Copy ID: {$copy->getId()}, Status: {$copy->getStatus()}, Access: {$copy->getAccessType()}\n";
}
echo "\n";

// Simulate API request
$url = 'http://localhost:8080/api/loans';
$data = [
    'userId' => $testUser->getId(),
    'bookId' => $testBook->getId()
];

// Login first to get token
$loginUrl = 'http://localhost:8080/api/auth/login';
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
    echo "❌ Login failed: $loginResponse\n";
    exit(1);
}

$loginJson = json_decode($loginResponse, true);
$token = $loginJson['token'] ?? null;

if (!$token) {
    echo "❌ No token in login response\n";
    exit(1);
}

echo "✅ Login successful, token obtained\n\n";

// Now try to create loan
echo "Attempting to create loan...\n";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n\n";

if ($httpCode === 201) {
    echo "✅ Loan created successfully!\n";
} else {
    echo "❌ Loan creation failed\n";
    $errorJson = json_decode($response, true);
    if (isset($errorJson['error'])) {
        echo "Error: {$errorJson['error']}\n";
    }
    if (isset($errorJson['message'])) {
        echo "Message: {$errorJson['message']}\n";
    }
}

echo "\n";
