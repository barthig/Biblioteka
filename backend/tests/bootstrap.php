<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$cacheDir = dirname(__DIR__) . '/var/cache/test';
if (is_dir($cacheDir)) {
    $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($cacheDir, \FilesystemIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getPathname());
            continue;
        }

        unlink($file->getPathname());
    }

    rmdir($cacheDir);
}