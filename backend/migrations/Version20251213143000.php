<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add starter taste embedding for cold-start recommendations.
 */
final class Version20251213143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add taste embedding to app_user for cold-start recommendations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS vector');
        $this->addSql('ALTER TABLE app_user ADD taste_embedding vector(1536) DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN app_user.taste_embedding IS \'(DC2Type:vector)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP taste_embedding');
    }
}
