<?php
namespace App\Service\System;

use App\Entity\BackupRecord;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class BackupService
{
    private string $backupDir;
    private string $databaseUrl;

    public function __construct(
        private EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')] string $projectDir,
        #[Autowire('%env(DATABASE_URL)%')] string $databaseUrl = ''
    ) {
        $this->backupDir = $projectDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'backups';
        $this->databaseUrl = $databaseUrl;
    }

    public function createBackup(?string $initiator = null, ?string $note = null): BackupRecord
    {
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }

        $timestamp = (new \DateTimeImmutable())->format('Ymd_His');
        $baseName = sprintf('backup_%s', $timestamp);
        $fileName = $baseName . '.sql.gz';
        $filePath = $this->backupDir . DIRECTORY_SEPARATOR . $fileName;

        $errorMessage = $this->createDatabaseDump($filePath);
        $status = $errorMessage === null ? 'completed' : 'failed';

        if ($errorMessage !== null) {
            $errorFileName = $baseName . '.error.txt';
            $errorFilePath = $this->backupDir . DIRECTORY_SEPARATOR . $errorFileName;
            file_put_contents($errorFilePath, $errorMessage);
            $fileName = $errorFileName;
            $filePath = $errorFilePath;
        }

        $record = (new BackupRecord())
            ->setFileName($fileName)
            ->setFilePath($filePath)
            ->setFileSize((int) (is_file($filePath) ? filesize($filePath) : 0))
            ->setStatus($status)
            ->setInitiatedBy($initiator);

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $record;
    }

    public function getBackupDirectory(): string
    {
        return $this->backupDir;
    }

    private function createDatabaseDump(string $filePath): ?string
    {
        // Try injected parameter first, then fall back to env
        $databaseUrl = $this->databaseUrl;
        if ($databaseUrl === '') {
            $databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? getenv('DATABASE_URL') ?: '';
        }
        
        if ($databaseUrl === '' || $databaseUrl === false) {
            return 'Missing DATABASE_URL environment variable. Injected: ' . ($this->databaseUrl ?: 'empty');
        }
        
        // Remove Doctrine query parameters like ?serverVersion=15&charset=utf8
        $databaseUrl = preg_replace('/\?.*$/', '', $databaseUrl);

        $config = $this->parseDatabaseUrl($databaseUrl);
        if ($config === null) {
            return sprintf('Unable to parse DATABASE_URL: %s', substr($databaseUrl, 0, 50) . '...');
        }

        $command = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -p %s -U %s -d %s --format=plain --no-owner --no-acl | gzip -9 > %s',
            escapeshellarg($config['password']),
            escapeshellarg($config['host']),
            escapeshellarg((string) $config['port']),
            escapeshellarg($config['user']),
            escapeshellarg($config['dbname']),
            escapeshellarg($filePath)
        );

        $output = [];
        $exitCode = 0;
        exec($command . ' 2>&1', $output, $exitCode);

        if ($exitCode !== 0 || !is_file($filePath) || filesize($filePath) === 0) {
            $details = $output ? implode("\n", $output) : 'Unknown error';
            if (is_file($filePath)) {
                @unlink($filePath);
            }
            return 'Backup failed: ' . $details;
        }

        return null;
    }

    /**
     * @return array{host:string,port:int,user:string,password:string,dbname:string}|null
     */
    private function parseDatabaseUrl(string $databaseUrl): ?array
    {
        $parts = parse_url($databaseUrl);
        if ($parts === false) {
            return null;
        }

        $host = $parts['host'] ?? null;
        $user = $parts['user'] ?? null;
        $pass = $parts['pass'] ?? '';
        $port = isset($parts['port']) ? (int) $parts['port'] : 5432;
        $path = $parts['path'] ?? '';
        $dbname = ltrim($path, '/');

        if (!$host || !$user || $dbname === '') {
            return null;
        }

        return [
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'password' => $pass,
            'dbname' => $dbname,
        ];
    }
}


