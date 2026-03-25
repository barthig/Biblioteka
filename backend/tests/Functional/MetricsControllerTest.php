<?php

namespace App\Tests\Functional;

class MetricsControllerTest extends ApiTestCase
{
    public function testMetricsEndpointReturnsPrometheusPayload(): void
    {
        $client = $this->createClientWithoutSecret();

        $client->request('GET', '/metrics');

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);
        $this->assertStringContainsString('text/plain', (string) $client->getResponse()->headers->get('content-type'));
        $this->assertStringContainsString('php_info', $content);
        $this->assertStringContainsString('php_memory_usage_bytes', $content);
    }
}

