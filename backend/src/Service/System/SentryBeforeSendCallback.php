<?php
declare(strict_types=1);

namespace App\Service\System;

use Sentry\Event;
use Sentry\EventHint;

/**
 * Callback wywoływany przed wysłaniem eventu do Sentry
 * Służy do filtrowania wrażliwych danych
 */
class SentryBeforeSendCallback
{
    /**
     * Filtruje wrażliwe dane przed wysłaniem do Sentry
     */
    public static function beforeSend(Event $event, ?EventHint $hint): ?Event
    {
        // Usuń wrażliwe nagłówki
        $request = $event->getRequest();
        if ($request) {
            $headers = $request['headers'] ?? [];
            
            // Lista wrażliwych nagłówków do usunięcia
            $sensitiveHeaders = [
                'authorization',
                'x-api-secret',
                'cookie',
                'set-cookie',
                'x-csrf-token',
            ];
            
            foreach ($sensitiveHeaders as $header) {
                if (isset($headers[$header])) {
                    $headers[$header] = '[FILTERED]';
                }
            }
            
            $request['headers'] = $headers;
            $event->setRequest($request);
        }
        
        // Usuń wrażliwe dane z kontekstu użytkownika
        // For maximum privacy, we don't send user data to Sentry
        $event->setUser(null);
        
        // Filtruj zmienne środowiskowe
        $extra = $event->getExtra();
        if (isset($extra['environment'])) {
            $env = $extra['environment'];
            $sensitiveEnvVars = [
                'DATABASE_URL',
                'JWT_SECRET',
                'API_SECRET',
                'SENTRY_DSN',
                'MAILER_DSN',
            ];
            
            foreach ($sensitiveEnvVars as $var) {
                if (isset($env[$var])) {
                    $env[$var] = '[FILTERED]';
                }
            }
            
            $extra['environment'] = $env;
            $event->setExtra($extra);
        }
        
        return $event;
    }
    
}


