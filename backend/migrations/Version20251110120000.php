<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251110120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add extended bibliographic metadata to book entity for advanced search support';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE book ADD publisher VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE book ADD publication_year SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE book ADD resource_type VARCHAR(60) DEFAULT NULL');
        $this->addSql('ALTER TABLE book ADD signature VARCHAR(60) DEFAULT NULL');
    $this->addSql('CREATE INDEX idx_book_publisher ON book (publisher)');
    $this->addSql('CREATE INDEX idx_book_publication_year ON book (publication_year)');
    $this->addSql('CREATE UNIQUE INDEX uniq_book_signature ON book (signature) WHERE signature IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
    $this->addSql('DROP INDEX IF EXISTS uniq_book_signature');
    $this->addSql('DROP INDEX IF EXISTS idx_book_publication_year');
    $this->addSql('DROP INDEX IF EXISTS idx_book_publisher');
        $this->addSql('ALTER TABLE book DROP publisher');
        $this->addSql('ALTER TABLE book DROP publication_year');
        $this->addSql('ALTER TABLE book DROP resource_type');
        $this->addSql('ALTER TABLE book DROP signature');
    }
}
