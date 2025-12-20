<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add user book interactions for recommendation tracking.
 */
final class Version20251213140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user book interaction table for recommendation tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_book_interaction (id SERIAL NOT NULL, user_id INT NOT NULL, book_id INT NOT NULL, type VARCHAR(20) NOT NULL, rating SMALLINT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX user_book_interaction_user_idx ON user_book_interaction (user_id)');
        $this->addSql('ALTER TABLE user_book_interaction ADD CONSTRAINT FK_USER_BOOK_INTERACTION_USER FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_book_interaction ADD CONSTRAINT FK_USER_BOOK_INTERACTION_BOOK FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_book_interaction');
    }
}
