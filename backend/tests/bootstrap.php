<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$testEnv = 'test';
putenv('APP_ENV=' . $testEnv);
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = $testEnv;
putenv('APP_DEBUG=0');
$_ENV['APP_DEBUG'] = $_SERVER['APP_DEBUG'] = '0';
putenv('OPENAI_API_KEY=test-openai-key');
$_ENV['OPENAI_API_KEY'] = $_SERVER['OPENAI_API_KEY'] = 'test-openai-key';

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
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS),
        RecursiveIteratorIterator::CHILD_FIRST,
        RecursiveIteratorIterator::CATCH_GET_CHILD
    );

    foreach ($iterator as $file) {
        $pathname = $file->getPathname();
        if ($file->isDir()) {
            @rmdir($pathname);
            continue;
        }

        @unlink($pathname);
    }

    @rmdir($cacheDir);
}

