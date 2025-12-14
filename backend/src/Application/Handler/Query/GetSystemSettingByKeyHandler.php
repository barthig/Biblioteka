<?php
namespace App\Application\Handler\Query;

use App\Application\Query\SystemSetting\GetSystemSettingByKeyQuery;
use App\Entity\SystemSetting;
use App\Repository\SystemSettingRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetSystemSettingByKeyHandler
{
    public function __construct(
        private SystemSettingRepository $systemSettingRepository
    ) {
    }

    public function __invoke(GetSystemSettingByKeyQuery $query): SystemSetting
    {
        $setting = $this->systemSettingRepository->findOneByKey($query->key);

        if (!$setting) {
            throw new NotFoundHttpException('System setting not found');
        }

        return $setting;
    }
}
