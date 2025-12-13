<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add expiredAt column to reservation table to separate expiration from cancellation
 */
final class Version20251213120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add expiredAt column to reservation table for better audit trail';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation ADD expired_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN reservation.expired_at IS \'(DC2Type:datetime_immutable)\'');
        
        // Migrate existing EXPIRED reservations to use expiredAt instead of cancelledAt
        $this->addSql('UPDATE reservation SET expired_at = cancelled_at WHERE status = \'EXPIRED\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation DROP expired_at');
    }
}
