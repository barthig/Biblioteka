<?php

declare(strict_types=1);

namespace App\Security;

final class PublicRouteMatcher
{
    /**
     * @var array<string, string[]>
     */
    private const EXACT_PUBLIC_ROUTES = [
        '/api/auth/login' => ['POST'],
        '/api/auth/register' => ['POST'],
        '/api/auth/refresh' => ['POST'],
        '/api/health' => ['GET'],
        '/health' => ['GET'],
        '/api/docs' => ['GET'],
        '/api/docs.json' => ['GET'],
    ];

    /**
     * @var array<int, array{pattern: string, methods: string[]}>
     */
    private const PUBLIC_PATTERNS = [
        ['pattern' => '#^/api/auth/verify/.+$#', 'methods' => ['GET']],
        ['pattern' => '#^/api/books$#', 'methods' => ['GET']],
        ['pattern' => '#^/api/books/filters$#', 'methods' => ['GET']],
        ['pattern' => '#^/api/books/recommended$#', 'methods' => ['GET']],
        ['pattern' => '#^/api/books/popular$#', 'methods' => ['GET']],
        ['pattern' => '#^/api/books/new$#', 'methods' => ['GET']],
        ['pattern' => '#^/api/books/\d+$#', 'methods' => ['GET']],
        ['pattern' => '#^/api/books/\d+/cover$#', 'methods' => ['GET']],
        ['pattern' => '#^/api/books/\d+/availability$#', 'methods' => ['GET']],
        ['pattern' => '#^/api/books/\d+/ratings$#', 'methods' => ['GET']],
        ['pattern' => '#^/api/collections$#', 'methods' => ['GET']],
        ['pattern' => '#^/api/collections/\d+$#', 'methods' => ['GET']],
        ['pattern' => '#^/api/announcements$#', 'methods' => ['GET']],
        ['pattern' => '#^/api/announcements/\d+$#', 'methods' => ['GET']],
        ['pattern' => '#^/api/library/hours$#', 'methods' => ['GET']],
        ['pattern' => '#^/api/library-hours$#', 'methods' => ['GET']],
    ];

    public function isPublicPath(string $path, string $method): bool
    {
        $method = strtoupper($method);
        $normalizedPath = $this->normalizePath($path);

        if ($this->isDebugTestLoginRoute($path, $method)) {
            return true;
        }

        if ($this->isExactPublicPath($path, $method) || $this->isExactPublicPath($normalizedPath, $method)) {
            return true;
        }

        foreach (self::PUBLIC_PATTERNS as $entry) {
            if (!in_array($method, $entry['methods'], true)) {
                continue;
            }

            if (preg_match($entry['pattern'], $path) || preg_match($entry['pattern'], $normalizedPath)) {
                return true;
            }
        }

        return false;
    }

    private function isExactPublicPath(string $path, string $method): bool
    {
        return isset(self::EXACT_PUBLIC_ROUTES[$path])
            && in_array($method, self::EXACT_PUBLIC_ROUTES[$path], true);
    }

    private function isDebugTestLoginRoute(string $path, string $method): bool
    {
        if ($path !== '/api/test-login' || $method !== 'POST') {
            return false;
        }

        $env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'prod');
        return in_array($env, ['dev', 'test'], true);
    }

    private function normalizePath(string $path): string
    {
        return preg_replace('#\{[^/]+\}#', '1', $path) ?? $path;
    }
}
