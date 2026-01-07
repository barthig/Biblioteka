<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260115090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add UI preference columns to app_user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD COLUMN IF NOT EXISTS theme VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD COLUMN IF NOT EXISTS font_size VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD COLUMN IF NOT EXISTS language VARCHAR(5) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP COLUMN IF EXISTS language');
        $this->addSql('ALTER TABLE app_user DROP COLUMN IF EXISTS font_size');
        $this->addSql('ALTER TABLE app_user DROP COLUMN IF EXISTS theme');
    }
}
