<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\GetSystemSettingHandler;
use App\Application\Query\SystemSetting\GetSystemSettingQuery;
use App\Entity\SystemSetting;
use App\Repository\SystemSettingRepository;
use PHPUnit\Framework\TestCase;

class GetSystemSettingHandlerTest extends TestCase
{
    private SystemSettingRepository $systemSettingRepository;
    private GetSystemSettingHandler $handler;

    protected function setUp(): void
    {
        $this->systemSettingRepository = $this->createMock(SystemSettingRepository::class);
        $this->handler = new GetSystemSettingHandler($this->systemSettingRepository);
    }

    public function testGetSystemSettingSuccess(): void
    {
        $setting = $this->createMock(SystemSetting::class);
        $this->systemSettingRepository->method('find')->with(1)->willReturn($setting);

        $query = new GetSystemSettingQuery(settingId: 1);
        $result = ($this->handler)($query);

        $this->assertSame($setting, $result);
    }
}
