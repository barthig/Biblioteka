<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add HNSW index for faster cosine similarity search on embeddings.
 */
final class Version20251213152000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add HNSW index on book.embedding for cosine distance queries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS vector');
        $this->addSql('CREATE INDEX IF NOT EXISTS book_embedding_hnsw_idx ON book USING hnsw (embedding vector_cosine_ops)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS book_embedding_hnsw_idx');
    }
}
