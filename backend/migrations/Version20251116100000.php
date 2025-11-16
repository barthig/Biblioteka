<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251116100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional target age group metadata for books.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE book ADD target_age_group VARCHAR(24) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE book DROP COLUMN target_age_group');
    }
}
