<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/bootstrap.php';

use App\Kernel;

$kernel = new Kernel($_ENV['APP_ENV'], (bool)$_ENV['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

echo "\n=== Checking Target Audience ===\n\n";

$announcements = $em->getRepository(\App\Entity\Announcement::class)->findBy(['status' => 'published']);

foreach ($announcements as $announcement) {
    echo "ID: {$announcement->getId()} - {$announcement->getTitle()}\n";
    echo "  Target Audience: " . json_encode($announcement->getTargetAudience()) . "\n";
    echo "  isActive(): " . ($announcement->isActive() ? 'YES' : 'NO') . "\n";
    echo "  isVisibleForUser(null): " . ($announcement->isVisibleForUser(null) ? 'YES' : 'NO') . "\n";
    echo "\n";
}
