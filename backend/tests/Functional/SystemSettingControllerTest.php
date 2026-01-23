<?php
namespace App\Tests\Functional;

use App\Entity\SystemSetting;

class SystemSettingControllerTest extends ApiTestCase
{
    public function testListRequiresAdmin(): void
    {
        $client = $this->createAuthenticatedClientWithoutApiSecret($this->createUser('reader@example.com'));
        $this->sendRequest($client, 'GET', '/api/system-settings');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateUpdateAndDeleteSetting(): void
    {
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        $client = $this->createAuthenticatedClient($admin);

        $this->jsonRequest($client, 'POST', '/api/system-settings', [
            'key' => 'site_name',
            'value' => 'Library',
            'valueType' => 'string'
        ]);
        $this->assertResponseStatusCodeSame(201);

        $setting = $this->entityManager->getRepository(SystemSetting::class)->findOneBy(['settingKey' => 'site_name']);
        $this->assertNotNull($setting);

        $this->jsonRequest($client, 'PUT', '/api/system-settings/' . $setting->getId(), [
            'value' => 'Library Updated'
        ]);
        $this->assertResponseStatusCodeSame(200);

        $this->sendRequest($client, 'DELETE', '/api/system-settings/' . $setting->getId());
        $this->assertResponseStatusCodeSame(204);
    }
}
