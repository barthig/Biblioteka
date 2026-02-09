<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\SystemSetting\ListSystemSettingsQuery;
use App\Repository\SystemSettingRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class ListSystemSettingsHandler
{
    public function __construct(
        private SystemSettingRepository $systemSettingRepository
    ) {
    }

    public function __invoke(ListSystemSettingsQuery $query): array
    {
        $offset = ($query->page - 1) * $query->limit;
        
        return $this->systemSettingRepository->findBy(
            [],
            ['settingKey' => 'ASC'],
            $query->limit,
            $offset
        );
    }
}
