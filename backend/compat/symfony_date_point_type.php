<?php

declare(strict_types=1);

namespace Symfony\Bridge\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Symfony\Component\Clock\DatePoint;

if (!class_exists(DatePointType::class, false)) {
    /**
     * Compatibility shim for environments where doctrine-bridge does not
     * provide DatePointType but Doctrine configuration still attempts to load it.
     */
    final class DatePointType extends DateTimeImmutableType
    {
        public function convertToPHPValue($value, AbstractPlatform $platform): ?DatePoint
        {
            $dateTime = parent::convertToPHPValue($value, $platform);

            if (null === $dateTime) {
                return null;
            }

            return $dateTime instanceof DatePoint ? $dateTime : DatePoint::createFromInterface($dateTime);
        }
    }
}
