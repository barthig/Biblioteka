<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\SystemSetting\DeleteSystemSettingCommand;
use App\Application\Handler\Command\DeleteSystemSettingHandler;
use App\Entity\SystemSetting;
use App\Repository\SystemSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DeleteSystemSettingHandlerTest extends TestCase
{
    private SystemSettingRepository $systemSettingRepository;
    private EntityManagerInterface $entityManager;
    private DeleteSystemSettingHandler $handler;

    protected function setUp(): void
    {
        $this->systemSettingRepository = $this->createMock(SystemSettingRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new DeleteSystemSettingHandler($this->systemSettingRepository, $this->entityManager);
    }

    public function testDeleteSystemSettingSuccess(): void
    {
        $setting = $this->createMock(SystemSetting::class);
        $this->systemSettingRepository->method('find')->with(1)->willReturn($setting);
        $this->entityManager->expects($this->once())->method('remove')->with($setting);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new DeleteSystemSettingCommand(settingId: 1);
        ($this->handler)($command);

        $this->assertTrue(true);
    }

    public function testThrowsExceptionWhenSettingNotFound(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->systemSettingRepository->method('find')->with(999)->willReturn(null);

        $command = new DeleteSystemSettingCommand(settingId: 999);
        ($this->handler)($command);
    }
}
