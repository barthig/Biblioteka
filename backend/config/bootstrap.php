<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$envFile = dirname(__DIR__) . '/.env';

if (class_exists('Symfony\Component\Dotenv\Dotenv')) {
    $dotenvClass = 'Symfony\Component\Dotenv\Dotenv';
    (new $dotenvClass())->usePutenv()->bootEnv($envFile);
} elseif (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"' ");

        $existing = getenv($name);
        if ($existing !== false) {
            $_ENV[$name] = $_SERVER[$name] = $existing;
            continue;
        }

        putenv(sprintf('%s=%s', $name, $value));
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
