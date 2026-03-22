<?php

namespace App\Tests\Integration;

use App\Tests\Functional\ApiTestCase;

class RateLimitIntegrationTest extends ApiTestCase
{
    public function testAnonymousRateLimit(): void
    {
        $client = $this->createClientWithoutSecret();
        $responses = [];

        for ($i = 0; $i < 305; $i++) {
            $this->sendRequest($client, 'GET', '/api/books');
            $responses[] = $client->getResponse()->getStatusCode();
        }

        $this->assertContains(200, $responses);
        $this->assertContains(429, $responses);
    }

    public function testRateLimitHeaders(): void
    {
        $client = $this->createClientWithoutSecret();
        $this->sendRequest($client, 'GET', '/api/books');

        $response = $client->getResponse();

        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
        $this->assertTrue($response->headers->has('X-RateLimit-Reset'));

        $limit = $response->headers->get('X-RateLimit-Limit');
        $this->assertSame(300, (int) $limit);
    }

    public function testLoginRateLimit(): void
    {
        $client = $this->createClientWithoutSecret();
        $responses = [];

        for ($i = 0; $i < 6; $i++) {
            $client->request('POST', '/api/auth/login', [], [], [
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([
                'email' => 'wrong@example.com',
                'password' => 'wrong'
            ]));

            $responses[] = $client->getResponse()->getStatusCode();
        }

        $this->assertContains(429, $responses);
    }
}
