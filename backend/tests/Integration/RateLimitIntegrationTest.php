<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Test integracyjny: Rate limiting
 */
class RateLimitIntegrationTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testAnonymousRateLimit(): void
    {
        // Wykonaj 31 żądań (limit to 30/min dla anonymous)
        $responses = [];
        
        for ($i = 0; $i < 31; $i++) {
            $this->client->request('GET', '/api/books', [], [], [
                'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
            ]);
            
            $responses[] = $this->client->getResponse()->getStatusCode();
        }
        
        // Pierwsze 30 powinno przejść
        $successCount = count(array_filter($responses, fn($code) => $code === 200));
        $this->assertGreaterThanOrEqual(29, $successCount); // Może być 29-30 z powodu timing
        
        // Ostatnie powinno być 429
        $this->assertContains(429, $responses);
    }

    public function testRateLimitHeaders(): void
    {
        $this->client->request('GET', '/api/books', [], [], [
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ]);
        
        $response = $this->client->getResponse();
        
        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
        $this->assertTrue($response->headers->has('X-RateLimit-Reset'));
        
        $limit = $response->headers->get('X-RateLimit-Limit');
        $this->assertGreaterThan(0, (int)$limit);
    }

    public function testLoginRateLimit(): void
    {
        // Login endpoint ma limit 5/15min
        $responses = [];
        
        for ($i = 0; $i < 6; $i++) {
            $this->client->request('POST', '/api/auth/login', [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
            ], json_encode([
                'email' => 'wrong@example.com',
                'password' => 'wrong'
            ]));
            
            $responses[] = $this->client->getResponse()->getStatusCode();
        }
        
        // Po 5 próbach powinno być 429
        $this->assertContains(429, $responses);
    }
}
