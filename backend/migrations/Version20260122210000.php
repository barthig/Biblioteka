<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260122210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update reservation status values from lowercase to uppercase';
    }

    public function up(Schema $schema): void
    {
        // Update existing reservation statuses to uppercase
        $this->addSql("UPDATE reservation SET status = 'ACTIVE' WHERE LOWER(status) = 'active'");
        $this->addSql("UPDATE reservation SET status = 'PREPARED' WHERE LOWER(status) = 'prepared'");
        $this->addSql("UPDATE reservation SET status = 'CANCELLED' WHERE LOWER(status) = 'cancelled'");
        $this->addSql("UPDATE reservation SET status = 'FULFILLED' WHERE LOWER(status) = 'fulfilled'");
        $this->addSql("UPDATE reservation SET status = 'EXPIRED' WHERE LOWER(status) = 'expired'");
    }

    public function down(Schema $schema): void
    {
        // Revert back to lowercase (optional)
        $this->addSql("UPDATE reservation SET status = 'active' WHERE status = 'ACTIVE'");
        $this->addSql("UPDATE reservation SET status = 'prepared' WHERE status = 'PREPARED'");
        $this->addSql("UPDATE reservation SET status = 'cancelled' WHERE status = 'CANCELLED'");
        $this->addSql("UPDATE reservation SET status = 'fulfilled' WHERE status = 'FULFILLED'");
        $this->addSql("UPDATE reservation SET status = 'expired' WHERE status = 'EXPIRED'");
    }
}
