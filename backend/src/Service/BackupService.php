<?php
namespace App\Service;

use App\Entity\BackupRecord;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class BackupService
{
    private string $backupDir;

    public function __construct(private EntityManagerInterface $entityManager, #[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->backupDir = $projectDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'backups';
    }

    public function createBackup(?string $initiator = null): BackupRecord
    {
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }

        $fileName = sprintf('backup_%s.json', (new \DateTimeImmutable())->format('Ymd_His'));
        $filePath = $this->backupDir . DIRECTORY_SEPARATOR . $fileName;

        $payload = [
            'generatedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'initiator' => $initiator,
            'note' => 'Snapshot placeholder created by BackupService',
        ];
        file_put_contents($filePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $record = (new BackupRecord())
            ->setFileName($fileName)
            ->setFilePath($filePath)
            ->setFileSize((int) filesize($filePath))
            ->setStatus('completed')
            ->setInitiatedBy($initiator);

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $record;
    }

    public function getBackupDirectory(): string
    {
        return $this->backupDir;
    }
}
