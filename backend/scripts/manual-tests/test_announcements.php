<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/bootstrap.php';

use App\Kernel;

$kernel = new Kernel($_ENV['APP_ENV'], (bool)$_ENV['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

echo "\n=== Checking Announcements ===\n\n";

// Check if there are any announcements in database
$announcements = $em->getRepository(\App\Entity\Announcement::class)->findAll();

echo "Total announcements in database: " . count($announcements) . "\n\n";

if (count($announcements) > 0) {
    echo "First 5 announcements:\n";
    foreach (array_slice($announcements, 0, 5) as $announcement) {
        echo "  ID: {$announcement->getId()}\n";
        echo "  Title: {$announcement->getTitle()}\n";
        echo "  Status: {$announcement->getStatus()}\n";
        echo "  Created: {$announcement->getCreatedAt()->format('Y-m-d H:i:s')}\n";
        echo "\n";
    }
}

// Test API endpoint
echo "Testing API endpoint...\n";
$url = 'http://localhost:8080/api/announcements';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: " . substr($response, 0, 500) . "\n\n";

if ($httpCode === 200) {
    $json = json_decode($response, true);
    echo "[OK] Endpoint works!\n";
    echo "Response has " . (isset($json['items']) ? count($json['items']) : 'unknown') . " items\n";
} else {
    echo "[ERR] Endpoint failed\n";
}

echo "\n";
