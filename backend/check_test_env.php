<?php
require __DIR__ . '/vendor/autoload.php';

$debug = (bool) ($_ENV['APP_DEBUG'] ?? true);
$kernel = new App\Kernel('test', $debug);
$kernel->boot();

echo 'env=' . $kernel->getEnvironment() . PHP_EOL;
echo 'has_test_service=' . ($kernel->getContainer()->has('test.service_container') ? 'yes' : 'no') . PHP_EOL;
echo 'has_test_client=' . ($kernel->getContainer()->has('test.client') ? 'yes' : 'no') . PHP_EOL;
