<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251110090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create book_digital_asset table for librarian digital resources';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS book_digital_asset CASCADE');
        $this->addSql('DROP SEQUENCE IF EXISTS book_digital_asset_id_seq CASCADE');

        $this->addSql('CREATE TABLE book_digital_asset (
            id INT GENERATED ALWAYS AS IDENTITY NOT NULL,
            book_id INT NOT NULL,
            label VARCHAR(255) NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            size INT NOT NULL,
            storage_name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DA8F62853A84A979 ON book_digital_asset (storage_name)');
        $this->addSql('CREATE INDEX IDX_DA8F628516A2B381 ON book_digital_asset (book_id)');
        $this->addSql('ALTER TABLE book_digital_asset ADD CONSTRAINT FK_DA8F628516A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS book_digital_asset CASCADE');
        $this->addSql('DROP SEQUENCE IF EXISTS book_digital_asset_id_seq CASCADE');
    }
}
