<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260123090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add avatar columns to app_user: avatar_storage_name, avatar_mime_type, avatar_updated_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD avatar_storage_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD avatar_mime_type VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD avatar_updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP avatar_storage_name');
        $this->addSql('ALTER TABLE app_user DROP avatar_mime_type');
        $this->addSql('ALTER TABLE app_user DROP avatar_updated_at');
    }
}
