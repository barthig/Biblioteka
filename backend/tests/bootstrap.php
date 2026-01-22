<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$testEnv = 'test';
putenv('APP_ENV=' . $testEnv);
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = $testEnv;
putenv('APP_DEBUG=0');
$_ENV['APP_DEBUG'] = $_SERVER['APP_DEBUG'] = '0';

$logDir = dirname(__DIR__) . '/var/log';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
ini_set('error_log', $logDir . '/phpunit.log');
ini_set('log_errors', '1');
ini_set('display_errors', '0');

$sqlitePath = dirname(__DIR__) . '/var/test.db';
$databaseUrl = 'sqlite:///' . $sqlitePath;
putenv('DATABASE_URL=' . $databaseUrl);
$_ENV['DATABASE_URL'] = $_SERVER['DATABASE_URL'] = $databaseUrl;

$cacheDir = dirname(__DIR__) . '/var/cache/test';
if (is_dir($cacheDir)) {
    $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($cacheDir, \FilesystemIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        if ($file->isDir()) {
            @rmdir($file->getPathname());
            continue;
        }

        @unlink($file->getPathname());
    }

    @rmdir($cacheDir);
}

// Ensure test database schema exists and seed baseline user for auth tests
$kernel = new \App\Kernel('test', false);
$kernel->boot();

$em = $kernel->getContainer()->get('doctrine')->getManager();
$connection = $em->getConnection();
if ($connection->getDatabasePlatform()->getName() !== 'sqlite') {
    $connection->executeStatement('CREATE EXTENSION IF NOT EXISTS vector');
}
$metadata = $em->getMetadataFactory()->getAllMetadata();
if (!empty($metadata)) {
    $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
    $tool->dropSchema($metadata);
    $tool->updateSchema($metadata, true);
}

$userRepo = $em->getRepository(\App\Entity\User::class);
$seedEmail = 'verified@example.com';

if (!$userRepo->findOneBy(['email' => $seedEmail])) {
    $user = new \App\Entity\User();
    $user->setEmail($seedEmail)
        ->setName('Verified User')
        ->setRoles(['ROLE_USER', 'ROLE_SYSTEM'])
        ->setPassword(password_hash('password123', PASSWORD_BCRYPT))
        ->markVerified();

    $em->persist($user);
    $em->flush();
}

$cachePool = $kernel->getContainer()->has('cache.rate_limiter')
    ? $kernel->getContainer()->get('cache.rate_limiter')
    : null;
if ($cachePool) {
    $cachePool->clear();
}

$kernel->shutdown();
