<?php
namespace App\Tests\Service;

use App\Entity\BackupRecord;
use App\Service\BackupService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class BackupServiceTest extends TestCase
{
    public function testCreateBackupWhenDatabaseUrlMissing(): void
    {
        $previous = getenv('DATABASE_URL');
        putenv('DATABASE_URL=');

        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'backup_test_' . uniqid();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(BackupRecord::class));
        $entityManager->expects($this->once())->method('flush');

        $service = new BackupService($entityManager, $tmpDir);
        $record = $service->createBackup('tester');

        $this->assertSame('failed', $record->getStatus());
        $this->assertFileExists($record->getFilePath());

        if ($previous !== false) {
            putenv('DATABASE_URL=' . $previous);
        }
    }
}
