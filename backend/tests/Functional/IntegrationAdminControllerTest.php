<?php
namespace App\Tests\Functional;

use App\Entity\IntegrationConfig;

class IntegrationAdminControllerTest extends ApiTestCase
{
    public function testListRequiresAdmin(): void
    {
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->sendRequest($client, 'GET', '/api/admin/integrations');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateUpdateAndTestIntegration(): void
    {
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        $client = $this->createAuthenticatedClient($admin);

        $this->jsonRequest($client, 'POST', '/api/admin/integrations', [
            'name' => 'Test Integration',
            'provider' => 'api'
        ]);
        $this->assertResponseStatusCodeSame(201);

        $config = $this->entityManager->getRepository(IntegrationConfig::class)->findOneBy(['name' => 'Test Integration']);
        $this->assertNotNull($config);

        $this->jsonRequest($client, 'PUT', '/api/admin/integrations/' . $config->getId(), [
            'enabled' => false
        ]);
        $this->assertResponseStatusCodeSame(200);

        $this->sendRequest($client, 'POST', '/api/admin/integrations/' . $config->getId() . '/test');
        $this->assertResponseStatusCodeSame(422);
    }
}
