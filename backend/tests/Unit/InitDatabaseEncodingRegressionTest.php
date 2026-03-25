<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class InitDatabaseEncodingRegressionTest extends TestCase
{
    public function testSeedFileDoesNotContainCommonMojibakeMarkers(): void
    {
        $sql = file_get_contents(__DIR__ . '/../../init-db-expanded-v2.sql');

        self::assertNotFalse($sql);

        foreach (['OgĹ', 'TreĹ', 'PeĹ', 'ZarzÄ', 'UĹ', 'KsiÄ', 'PrzedziaĹ', 'Obsluga wypozyczen'] as $marker) {
            self::assertStringNotContainsString($marker, $sql, sprintf('Found mojibake marker "%s" in seed SQL.', $marker));
        }
    }
}
