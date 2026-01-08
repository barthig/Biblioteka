<?php
namespace App\Tests\Functional;

use App\Entity\IntegrationConfig;
use App\Entity\StaffRole;
use App\Entity\SystemSetting;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class AdminSystemModuleTest extends ApiTestCase
{
    public function testSystemSettingsCrud(): void
    {
        $admin = $this->createUser('admin-settings@example.com', ['ROLE_ADMIN']);
        $client = $this->createAuthenticatedClient($admin);

        $payload = [
            'key' => 'loan_limit_standard',
            'value' => 5,
            'type' => SystemSetting::TYPE_INT,
            'description' => 'Default loan limit for standard members',
        ];
        $this->jsonRequest($client, 'POST', '/api/admin/system/settings', $payload);
        $this->assertResponseStatusCodeSame(201);
        $created = $this->getJsonResponse($client);
        self::assertSame('loan_limit_standard', $created['key']);
        self::assertSame(5, $created['value']);

        $this->sendRequest($client, 'GET', '/api/admin/system/settings');
        $this->assertResponseStatusCodeSame(200);
        $list = $this->getJsonResponse($client);
        self::assertNotEmpty($list['settings']);

        $this->jsonRequest($client, 'PUT', '/api/admin/system/settings/loan_limit_standard', [
            'value' => 7,
        ]);
        $this->assertResponseStatusCodeSame(200);
        $updated = $this->getJsonResponse($client);
        self::assertSame(7, $updated['value']);
    }

    public function testRoleCreationAndAssignment(): void
    {
        $admin = $this->createUser('admin-roles@example.com', ['ROLE_ADMIN']);
        $librarian = $this->createUser('librarian-candidate@example.com');
        $client = $this->createAuthenticatedClient($admin);

        $this->jsonRequest($client, 'POST', '/api/admin/system/roles', [
            'name' => 'Content Manager',
            'roleKey' => 'ROLE_CONTENT_MANAGER',
            'modules' => ['catalog', 'reports'],
            'description' => 'Grants access to catalogue curation modules',
        ]);
        $this->assertResponseStatusCodeSame(201);
        $createdRole = $this->getJsonResponse($client);
        self::assertSame('ROLE_CONTENT_MANAGER', $createdRole['roleKey']);

        $this->jsonRequest($client, 'POST', '/api/admin/system/roles/ROLE_CONTENT_MANAGER/assign', [
            'userId' => $librarian->getId(),
        ]);
        $this->assertResponseStatusCodeSame(200);
        $assignment = $this->getJsonResponse($client);
        self::assertContains('ROLE_CONTENT_MANAGER', $assignment['roles']);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $reloaded = $em->getRepository(User::class)->find($librarian->getId());
        self::assertNotNull($reloaded);
        self::assertContains('ROLE_CONTENT_MANAGER', $reloaded->getRoles());
    }

    public function testIntegrationLifecycle(): void
    {
        $admin = $this->createUser('admin-integrations@example.com', ['ROLE_ADMIN']);
        $client = $this->createAuthenticatedClient($admin);

        $this->jsonRequest($client, 'POST', '/api/admin/system/integrations', [
            'name' => 'Legimi',
            'provider' => 'legimi',
            'settings' => [
                'apiKey' => 'secret',
                'endpoint' => 'https://api.legimi.example',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $created = $this->getJsonResponse($client);
        self::assertSame('Legimi', $created['name']);
        $integrationId = $created['id'];

        $this->jsonRequest($client, 'PUT', '/api/admin/system/integrations/' . $integrationId, [
            'enabled' => false,
        ]);
        $this->assertResponseStatusCodeSame(200);
        $updated = $this->getJsonResponse($client);
        self::assertFalse($updated['enabled']);

        $this->jsonRequest($client, 'POST', '/api/admin/system/integrations/' . $integrationId . '/test');
        $this->assertResponseStatusCodeSame(200);
        $test = $this->getJsonResponse($client);
        self::assertSame('ok', $test['status']);
    }

    public function testBackupsAndLogs(): void
    {
        $admin = $this->createUser('admin-security@example.com', ['ROLE_ADMIN']);
        $client = $this->createAuthenticatedClient($admin);

        $projectDir = static::getContainer()->getParameter('kernel.project_dir');
        $logDir = $projectDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'log';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'app.log';
        file_put_contents($logFile, "[info] System started\n[warning] Sample warning\n", FILE_APPEND);

        $this->sendRequest($client, 'GET', '/api/admin/system/logs');
        $this->assertResponseStatusCodeSame(200);
        $logs = $this->getJsonResponse($client);
        self::assertGreaterThan(0, $logs['count']);

        $this->sendRequest($client, 'GET', '/api/admin/system/backups');
        $this->assertResponseStatusCodeSame(200);
        $before = $this->getJsonResponse($client);
        $existingCount = count($before['backups']);

        $this->jsonRequest($client, 'POST', '/api/admin/system/backups');
        $this->assertResponseStatusCodeSame(201);
        $created = $this->getJsonResponse($client);
        self::assertContains($created['status'], ['completed', 'failed']);

        $this->sendRequest($client, 'GET', '/api/admin/system/backups');
        $this->assertResponseStatusCodeSame(200);
        $after = $this->getJsonResponse($client);
        self::assertSame($existingCount + 1, count($after['backups']));
    }
}
