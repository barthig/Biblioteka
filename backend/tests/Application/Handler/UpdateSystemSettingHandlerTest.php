<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\SystemSetting\UpdateSystemSettingCommand;
use App\Application\Handler\Command\UpdateSystemSettingHandler;
use App\Entity\SystemSetting;
use App\Repository\SystemSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UpdateSystemSettingHandlerTest extends TestCase
{
    private SystemSettingRepository $systemSettingRepository;
    private EntityManagerInterface $entityManager;
    private UpdateSystemSettingHandler $handler;

    protected function setUp(): void
    {
        $this->systemSettingRepository = $this->createMock(SystemSettingRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new UpdateSystemSettingHandler($this->systemSettingRepository, $this->entityManager);
    }

    public function testUpdateSystemSettingSuccess(): void
    {
        $setting = $this->createMock(SystemSetting::class);
        $setting->expects($this->once())->method('setValueFromMixed')->with('new_value');
        
        $this->systemSettingRepository->method('find')->with(1)->willReturn($setting);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new UpdateSystemSettingCommand(settingId: 1, value: 'new_value');
        $result = ($this->handler)($command);

        $this->assertSame($setting, $result);
    }

    public function testThrowsExceptionWhenSettingNotFound(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->systemSettingRepository->method('find')->with(999)->willReturn(null);

        $command = new UpdateSystemSettingCommand(settingId: 999, value: 'test');
        ($this->handler)($command);
    }
}
