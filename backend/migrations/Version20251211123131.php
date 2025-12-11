<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211123131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create refresh_token table for JWT refresh tokens';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE SEQUENCE refresh_token_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        ');
        
        $this->addSql('
            CREATE TABLE refresh_token (
                id INT NOT NULL DEFAULT nextval(\'refresh_token_id_seq\'),
                user_id INT NOT NULL,
                token VARCHAR(255) NOT NULL,
                expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                is_revoked BOOLEAN NOT NULL DEFAULT FALSE,
                revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY(id),
                CONSTRAINT fk_refresh_token_user FOREIGN KEY (user_id) 
                    REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        ');
        
        $this->addSql('CREATE UNIQUE INDEX uniq_refresh_token ON refresh_token (token)');
        $this->addSql('CREATE INDEX idx_refresh_token ON refresh_token (token)');
        $this->addSql('CREATE INDEX idx_refresh_token_user ON refresh_token (user_id)');
        
        $this->addSql('COMMENT ON COLUMN refresh_token.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN refresh_token.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN refresh_token.revoked_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE refresh_token');
        $this->addSql('DROP SEQUENCE refresh_token_id_seq CASCADE');
    }
}
