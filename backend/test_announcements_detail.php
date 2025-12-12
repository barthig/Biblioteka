<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/bootstrap.php';

use App\Kernel;

$kernel = new Kernel($_ENV['APP_ENV'], (bool)$_ENV['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

echo "\n=== Checking Published Announcements ===\n\n";

$announcements = $em->getRepository(\App\Entity\Announcement::class)->findBy(['status' => 'published']);

echo "Published announcements: " . count($announcements) . "\n\n";

foreach ($announcements as $announcement) {
    echo "ID: {$announcement->getId()}\n";
    echo "  Title: {$announcement->getTitle()}\n";
    echo "  Status: {$announcement->getStatus()}\n";
    echo "  Published At: " . ($announcement->getPublishedAt() ? $announcement->getPublishedAt()->format('Y-m-d H:i:s') : 'NULL') . "\n";
    echo "  Expires At: " . ($announcement->getExpiresAt() ? $announcement->getExpiresAt()->format('Y-m-d H:i:s') : 'NULL') . "\n";
    echo "  Show On Homepage: " . ($announcement->isShowOnHomepage() ? 'YES' : 'NO') . "\n";
    echo "\n";
}

// Test the repository method directly
echo "Testing findActiveForUser(null):\n";
$repo = $em->getRepository(\App\Entity\Announcement::class);
$activeAnnouncements = $repo->findActiveForUser(null);
echo "Found: " . count($activeAnnouncements) . " announcements\n\n";

echo "\n";
