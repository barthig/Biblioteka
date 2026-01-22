<?php
namespace App\Tests\Service;

use App\Entity\SystemSetting;
use App\Repository\SystemSettingRepository;
use App\Service\SystemSettingsService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class SystemSettingsServiceTest extends TestCase
{
    public function testGetReturnsDefaultWhenMissing(): void
    {
        $repo = $this->createMock(SystemSettingRepository::class);
        $repo->method('findOneBy')->willReturn(null);
        $em = $this->createMock(EntityManagerInterface::class);

        $service = new SystemSettingsService($repo, $em);
        $this->assertSame(5, $service->get('loanLimitPerUser'));
    }

    public function testSetPersistsAndCachesValue(): void
    {
        $repo = $this->createMock(SystemSettingRepository::class);
        $repo->method('findOneBy')->willReturn(null);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($this->isInstanceOf(SystemSetting::class));
        $em->expects($this->once())->method('flush');

        $service = new SystemSettingsService($repo, $em);
        $service->set('notificationsEnabled', true);

        $this->assertTrue($service->areNotificationsEnabled());
    }
}
