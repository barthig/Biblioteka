<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create notification_log table for tracking dispatched reminders.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('notification_log')) {
            return;
        }

        $this->addSql('CREATE TABLE notification_log (id SERIAL NOT NULL, user_id INT NOT NULL, type VARCHAR(64) NOT NULL, channel VARCHAR(32) NOT NULL, fingerprint VARCHAR(96) NOT NULL, payload JSON DEFAULT NULL, status VARCHAR(32) NOT NULL, error_message VARCHAR(255) DEFAULT NULL, sent_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uq_notification_fingerprint_channel ON notification_log (fingerprint, channel)');
        $this->addSql('CREATE INDEX idx_notification_user ON notification_log (user_id)');
        $this->addSql('COMMENT ON COLUMN notification_log.sent_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE notification_log ADD CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('notification_log')) {
            return;
        }

        $this->addSql('DROP TABLE notification_log');
    }
}
