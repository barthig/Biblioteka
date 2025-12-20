<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add full-text search vector and index for hybrid search.
 */
final class Version20251213154000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add book search_vector tsvector column and GIN index for hybrid search';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE book ADD COLUMN IF NOT EXISTS search_vector tsvector GENERATED ALWAYS AS (to_tsvector('simple', coalesce(title, '') || ' ' || coalesce(description, ''))) STORED");
        $this->addSql('CREATE INDEX IF NOT EXISTS book_search_vector_idx ON book USING GIN (search_vector)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS book_search_vector_idx');
        $this->addSql('ALTER TABLE book DROP COLUMN IF EXISTS search_vector');
    }
}
