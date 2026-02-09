<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\SystemSetting\GetSystemSettingQuery;
use App\Entity\SystemSetting;
use App\Repository\SystemSettingRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetSystemSettingHandler
{
    public function __construct(
        private SystemSettingRepository $systemSettingRepository
    ) {
    }

    public function __invoke(GetSystemSettingQuery $query): SystemSetting
    {
        $setting = $this->systemSettingRepository->find($query->settingId);
        
        if (!$setting) {
            throw new NotFoundHttpException('System setting not found');
        }

        return $setting;
    }
}
