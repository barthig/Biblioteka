<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migracja dodająca tabelę announcement (ogłoszenia)
 */
final class Version20251211121430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Dodaje tabelę announcement dla systemu ogłoszeń';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE announcement_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        
        $this->addSql('CREATE TABLE announcement (
            id INT NOT NULL, 
            created_by_id INT NOT NULL, 
            title VARCHAR(255) NOT NULL, 
            content TEXT NOT NULL, 
            type VARCHAR(20) NOT NULL, 
            status VARCHAR(20) NOT NULL, 
            is_pinned BOOLEAN NOT NULL, 
            show_on_homepage BOOLEAN NOT NULL, 
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
            expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
            target_audience JSON DEFAULT NULL, 
            PRIMARY KEY(id)
        )');
        
        $this->addSql('CREATE INDEX IDX_4DB9D91CB03A8386 ON announcement (created_by_id)');
        $this->addSql('CREATE INDEX idx_announcement_status ON announcement (status)');
        $this->addSql('CREATE INDEX idx_announcement_published ON announcement (published_at)');
        $this->addSql('CREATE INDEX idx_announcement_expires ON announcement (expires_at)');
        
        $this->addSql('COMMENT ON COLUMN announcement.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN announcement.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN announcement.published_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN announcement.expires_at IS \'(DC2Type:datetime_immutable)\'');
        
        $this->addSql('ALTER TABLE announcement ADD CONSTRAINT FK_4DB9D91CB03A8386 FOREIGN KEY (created_by_id) REFERENCES app_user (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE announcement DROP CONSTRAINT FK_4DB9D91CB03A8386');
        $this->addSql('DROP TABLE announcement');
        $this->addSql('DROP SEQUENCE announcement_id_seq CASCADE');
    }
}
