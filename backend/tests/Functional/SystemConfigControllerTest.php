<?php
namespace App\Tests\Functional;

use App\Entity\SystemSetting;

class SystemConfigControllerTest extends ApiTestCase
{
    public function testListRequiresAdmin(): void
    {
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->sendRequest($client, 'GET', '/api/admin/system/settings');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateAndUpdateSetting(): void
    {
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        $client = $this->createAuthenticatedClient($admin);

        $this->jsonRequest($client, 'POST', '/api/admin/system/settings', [
            'key' => 'site_name',
            'value' => 'Library'
        ]);
        $this->assertResponseStatusCodeSame(201);

        $setting = $this->entityManager->getRepository(SystemSetting::class)->findOneBy(['settingKey' => 'site_name']);
        $this->assertNotNull($setting);

        $this->jsonRequest($client, 'PUT', '/api/admin/system/settings/site_name', [
            'value' => 'Library Updated'
        ]);
        $this->assertResponseStatusCodeSame(200);
    }
}
