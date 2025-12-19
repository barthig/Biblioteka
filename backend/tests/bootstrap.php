<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$testEnv = 'test';
putenv('APP_ENV=' . $testEnv);
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = $testEnv;
putenv('APP_DEBUG=1');
$_ENV['APP_DEBUG'] = $_SERVER['APP_DEBUG'] = '1';

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
$kernel = new \App\Kernel('test', true);
$kernel->boot();

$em = $kernel->getContainer()->get('doctrine')->getManager();
$em->getConnection()->executeStatement('CREATE EXTENSION IF NOT EXISTS vector');
$metadata = $em->getMetadataFactory()->getAllMetadata();
if (!empty($metadata)) {
    $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
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
