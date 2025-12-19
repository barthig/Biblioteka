<?php
namespace App\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;

class VectorType extends Type
{
    public const NAME = 'vector';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $dimensions = $column['dimensions'] ?? $column['length'] ?? null;
        if ($dimensions === null) {
            return 'vector';
        }

        return sprintf('vector(%d)', (int) $dimensions);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (!is_array($value)) {
            throw ConversionException::conversionFailedInvalidType(
                $value,
                self::NAME,
                ['array', 'string', 'null']
            );
        }

        $floats = array_map(static fn ($v) => (float) $v, $value);
        return '[' . implode(',', $floats) . ']';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
            $value = substr($value, 1, -1);
        }

        $value = trim($value);
        if ($value === '') {
            return [];
        }

        $parts = array_map('trim', explode(',', $value));

        return array_map('floatval', $parts);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
