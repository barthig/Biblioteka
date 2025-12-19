<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add pgvector embedding column for semantic search.
 */
final class Version20251213131500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pgvector embedding column to book table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS vector');
        $this->addSql('ALTER TABLE book ADD embedding vector(1536) DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN book.embedding IS \'(DC2Type:vector)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE book DROP embedding');
    }
}
