<?php
namespace App\Tests\Functional;

use App\Entity\IntegrationConfig;

class IntegrationConfigControllerTest extends ApiTestCase
{
    public function testListRequiresAdmin(): void
    {
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->sendRequest($client, 'GET', '/api/integration-configs');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testListReturnsDataForAdmin(): void
    {
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        $client = $this->createAuthenticatedClient($admin);
        $this->sendRequest($client, 'GET', '/api/integration-configs');

        $this->assertResponseStatusCodeSame(200);
    }

    public function testCreateUpdateAndDeleteConfig(): void
    {
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        $client = $this->createAuthenticatedClient($admin);

        $this->jsonRequest($client, 'POST', '/api/integration-configs', [
            'name' => 'Test Integration',
            'provider' => 'api',
            'enabled' => true,
            'settings' => ['endpoint' => 'http://example.com']
        ]);

        $this->assertResponseStatusCodeSame(201);
        $config = $this->entityManager->getRepository(IntegrationConfig::class)->findOneBy(['name' => 'Test Integration']);
        $this->assertNotNull($config);

        $this->jsonRequest($client, 'PUT', '/api/integration-configs/' . $config->getId(), [
            'name' => 'Updated Integration',
            'enabled' => false
        ]);
        $this->assertResponseStatusCodeSame(200);

        $this->sendRequest($client, 'DELETE', '/api/integration-configs/' . $config->getId());
        $this->assertResponseStatusCodeSame(204);
    }
}
