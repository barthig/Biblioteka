<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class InitDatabaseSchemaRegressionTest extends TestCase
{
    private string $sql;

    protected function setUp(): void
    {
        $path = dirname(__DIR__, 2) . '/init-db-expanded-v2.sql';
        $contents = file_get_contents($path);

        self::assertNotFalse($contents, 'Unable to read init-db-expanded-v2.sql');

        $this->sql = $contents;
    }

    public function testAppUserTableContainsAvatarColumns(): void
    {
        $table = $this->extractCreateTable('app_user');

        self::assertStringContainsString('avatar_storage_name VARCHAR(255) DEFAULT NULL', $table);
        self::assertStringContainsString('avatar_mime_type VARCHAR(100) DEFAULT NULL', $table);
        self::assertStringContainsString('avatar_updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL', $table);
    }

    public function testBookTableContainsUpdatedAtColumn(): void
    {
        $table = $this->extractCreateTable('book');

        self::assertStringContainsString('updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL', $table);
    }

    public function testLoanTableContainsUpdatedAtColumn(): void
    {
        $table = $this->extractCreateTable('loan');

        self::assertStringContainsString('updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL', $table);
    }

    private function extractCreateTable(string $tableName): string
    {
        $pattern = sprintf('/CREATE TABLE %s \((.*?)\n\);/s', preg_quote($tableName, '/'));
        $matched = preg_match($pattern, $this->sql, $matches);

        self::assertSame(1, $matched, sprintf('CREATE TABLE definition for "%s" not found.', $tableName));

        return $matches[1];
    }
}
