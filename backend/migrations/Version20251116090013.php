<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251116090013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove legacy reader order table after decommissioning the feature.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('order_request')) {
            $this->addSql('DROP TABLE order_request');
        }

        $this->addSql('DROP SEQUENCE IF EXISTS order_request_id_seq CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE order_request (id SERIAL NOT NULL, book_id INT NOT NULL, book_copy_id INT DEFAULT NULL, user_id INT NOT NULL, status VARCHAR(20) NOT NULL, pickup_type VARCHAR(30) DEFAULT \'STORAGE_DESK\' NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, pickup_deadline TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, cancelled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, collected_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, expired_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_order_request_book ON order_request (book_id)');
        $this->addSql('CREATE INDEX idx_order_request_copy ON order_request (book_copy_id)');
        $this->addSql('CREATE INDEX idx_order_request_user ON order_request (user_id)');
        $this->addSql('COMMENT ON COLUMN order_request.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN order_request.pickup_deadline IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN order_request.cancelled_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN order_request.collected_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN order_request.expired_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE order_request ADD CONSTRAINT fk_cded26d416a2b381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_request ADD CONSTRAINT fk_cded26d43b550fe4 FOREIGN KEY (book_copy_id) REFERENCES book_copy (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_request ADD CONSTRAINT fk_cded26d4a76ed395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
