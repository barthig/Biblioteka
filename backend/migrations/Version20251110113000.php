<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251110113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add blocking and membership group columns to app_user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE app_user ADD blocked BOOLEAN DEFAULT FALSE NOT NULL");
        $this->addSql("ALTER TABLE app_user ADD membership_group VARCHAR(64) DEFAULT 'standard' NOT NULL");
        $this->addSql("ALTER TABLE app_user ADD loan_limit INT DEFAULT 5 NOT NULL");
        $this->addSql("ALTER TABLE app_user ADD blocked_reason VARCHAR(255) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP COLUMN blocked');
        $this->addSql('ALTER TABLE app_user DROP COLUMN membership_group');
        $this->addSql('ALTER TABLE app_user DROP COLUMN loan_limit');
        $this->addSql('ALTER TABLE app_user DROP COLUMN blocked_reason');
    }
}
