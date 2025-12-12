<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\ListSystemSettingsHandler;
use App\Application\Query\SystemSetting\ListSystemSettingsQuery;
use App\Repository\SystemSettingRepository;
use PHPUnit\Framework\TestCase;

class ListSystemSettingsHandlerTest extends TestCase
{
    private SystemSettingRepository $systemSettingRepository;
    private ListSystemSettingsHandler $handler;

    protected function setUp(): void
    {
        $this->systemSettingRepository = $this->createMock(SystemSettingRepository::class);
        $this->handler = new ListSystemSettingsHandler($this->systemSettingRepository);
    }

    public function testListSystemSettingsSuccess(): void
    {
        $this->systemSettingRepository->method('findBy')->willReturn([]);

        $query = new ListSystemSettingsQuery(page: 1, limit: 50);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
