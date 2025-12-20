<?php
namespace App\Tests\Unit;

use App\Doctrine\Type\VectorType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use PHPUnit\Framework\TestCase;

class VectorTypeTest extends TestCase
{
    public function testSqlDeclarationUsesDimensions(): void
    {
        $type = new VectorType();
        $platform = new PostgreSQLPlatform();

        $sql = $type->getSQLDeclaration(['dimensions' => 3], $platform);

        $this->assertSame('vector(3)', $sql);
    }

    public function testSqlDeclarationDefaultsWithoutDimensions(): void
    {
        $type = new VectorType();
        $platform = new PostgreSQLPlatform();

        $sql = $type->getSQLDeclaration([], $platform);

        $this->assertSame('vector', $sql);
    }

    public function testConvertsArrayToDatabaseValue(): void
    {
        $type = new VectorType();
        $platform = new PostgreSQLPlatform();

        $value = $type->convertToDatabaseValue([1, 2.5, '3'], $platform);

        $this->assertSame('[1,2.5,3]', $value);
    }

    public function testConvertsStringToDatabaseValue(): void
    {
        $type = new VectorType();
        $platform = new PostgreSQLPlatform();

        $value = $type->convertToDatabaseValue('[0.1,0.2]', $platform);

        $this->assertSame('[0.1,0.2]', $value);
    }

    public function testConvertsDatabaseValueToArray(): void
    {
        $type = new VectorType();
        $platform = new PostgreSQLPlatform();

        $value = $type->convertToPHPValue('[0.1, 0.2, 0.3]', $platform);

        $this->assertEquals([0.1, 0.2, 0.3], $value);
    }

    public function testConvertsEmptyDatabaseValueToEmptyArray(): void
    {
        $type = new VectorType();
        $platform = new PostgreSQLPlatform();

        $this->assertSame([], $type->convertToPHPValue('[]', $platform));
        $this->assertSame([], $type->convertToPHPValue('', $platform));
    }

    public function testRequiresSqlCommentHint(): void
    {
        $type = new VectorType();
        $platform = new PostgreSQLPlatform();

        $this->assertTrue($type->requiresSQLCommentHint($platform));
    }
}
