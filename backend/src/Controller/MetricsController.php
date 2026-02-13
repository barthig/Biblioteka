<?php
declare(strict_types=1);
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Prometheus metrics endpoint for the PHP backend.
 *
 * Exposes basic application metrics in Prometheus text format
 * so that the existing Prometheus instance can scrape the backend.
 */
class MetricsController extends AbstractController
{
    #[Route('/metrics', name: 'app_metrics', methods: ['GET'])]
    public function __invoke(): Response
    {
        $metrics = [];

        // PHP process metrics
        $metrics[] = '# HELP php_info PHP runtime information';
        $metrics[] = '# TYPE php_info gauge';
        $metrics[] = sprintf('php_info{version="%s"} 1', PHP_VERSION);

        // Memory usage
        $metrics[] = '# HELP php_memory_usage_bytes Current memory usage in bytes';
        $metrics[] = '# TYPE php_memory_usage_bytes gauge';
        $metrics[] = sprintf('php_memory_usage_bytes %d', memory_get_usage(true));

        $metrics[] = '# HELP php_memory_peak_bytes Peak memory usage in bytes';
        $metrics[] = '# TYPE php_memory_peak_bytes gauge';
        $metrics[] = sprintf('php_memory_peak_bytes %d', memory_get_peak_usage(true));

        // OPcache statistics (if available)
        if (function_exists('opcache_get_status')) {
            $opcache = @opcache_get_status(false);
            if ($opcache && is_array($opcache)) {
                $stats = $opcache['opcache_statistics'] ?? [];

                $metrics[] = '# HELP php_opcache_hits_total Number of OPcache cache hits';
                $metrics[] = '# TYPE php_opcache_hits_total counter';
                $metrics[] = sprintf('php_opcache_hits_total %d', $stats['hits'] ?? 0);

                $metrics[] = '# HELP php_opcache_misses_total Number of OPcache cache misses';
                $metrics[] = '# TYPE php_opcache_misses_total counter';
                $metrics[] = sprintf('php_opcache_misses_total %d', $stats['misses'] ?? 0);

                $metrics[] = '# HELP php_opcache_memory_used_bytes OPcache used memory';
                $metrics[] = '# TYPE php_opcache_memory_used_bytes gauge';
                $mem = $opcache['memory_usage'] ?? [];
                $metrics[] = sprintf('php_opcache_memory_used_bytes %d', $mem['used_memory'] ?? 0);
            }
        }

        // Uptime approximation via request time
        $metrics[] = '# HELP php_uptime_seconds Server request timestamp';
        $metrics[] = '# TYPE php_uptime_seconds gauge';
        $metrics[] = sprintf('php_uptime_seconds %d', time());

        $body = implode("\n", $metrics) . "\n";

        return new Response($body, 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }
}
