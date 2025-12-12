<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\SystemSetting\CreateSystemSettingCommand;
use App\Application\Handler\Command\CreateSystemSettingHandler;
use App\Entity\SystemSetting;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreateSystemSettingHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private CreateSystemSettingHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new CreateSystemSettingHandler($this->entityManager);
    }

    public function testCreateSystemSettingSuccess(): void
    {
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new CreateSystemSettingCommand(
            key: 'test_key',
            value: 'test_value',
            valueType: 'string',
            description: 'Test description'
        );

        $result = ($this->handler)($command);

        $this->assertInstanceOf(SystemSetting::class, $result);
    }
}
