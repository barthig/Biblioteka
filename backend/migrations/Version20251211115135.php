<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211115135 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add audit_logs table for tracking system operations';
    }

    public function up(Schema $schema): void
    {
        // Add only audit_logs table
        $this->addSql('CREATE SEQUENCE audit_logs_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE audit_logs (id INT NOT NULL, user_id INT DEFAULT NULL, entity_type VARCHAR(50) NOT NULL, entity_id INT DEFAULT NULL, action VARCHAR(20) NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, old_values TEXT DEFAULT NULL, new_values TEXT DEFAULT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_audit_entity ON audit_logs (entity_type, entity_id)');
        $this->addSql('CREATE INDEX idx_audit_action ON audit_logs (action)');
        $this->addSql('CREATE INDEX idx_audit_user ON audit_logs (user_id)');
        $this->addSql('CREATE INDEX idx_audit_created ON audit_logs (created_at)');
        $this->addSql('COMMENT ON COLUMN audit_logs.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE audit_logs ADD CONSTRAINT FK_D62F2858A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE audit_logs_id_seq CASCADE');
        $this->addSql('ALTER TABLE audit_logs DROP CONSTRAINT FK_D62F2858A76ED395');
        $this->addSql('DROP TABLE audit_logs');
    }
}
