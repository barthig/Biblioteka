<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251109101500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create normalized schema for authors, categories, books, users and loans';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE author (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BDAFD8C65E237E06 ON author (name)');

        $this->addSql('CREATE TABLE category (id SERIAL NOT NULL, name VARCHAR(120) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64C19C15E237E06 ON category (name)');

        $this->addSql('CREATE TABLE book (id SERIAL NOT NULL, author_id INT NOT NULL, title VARCHAR(255) NOT NULL, isbn VARCHAR(20) DEFAULT NULL, copies INT NOT NULL, total_copies INT NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_CBE5A331F675F31B ON book (author_id)');
        $this->addSql('ALTER TABLE book ADD CONSTRAINT FK_CBE5A331F675F31B FOREIGN KEY (author_id) REFERENCES author (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE book_category (book_id INT NOT NULL, category_id INT NOT NULL, PRIMARY KEY(book_id, category_id))');
        $this->addSql('CREATE INDEX IDX_C745FC58516A6F96 ON book_category (book_id)');
        $this->addSql('CREATE INDEX IDX_C745FC5812469DE2 ON book_category (category_id)');
        $this->addSql('ALTER TABLE book_category ADD CONSTRAINT FK_C745FC58516A6F96 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE book_category ADD CONSTRAINT FK_C745FC5812469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE app_user (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, name VARCHAR(255) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E9E7927C74 ON app_user (email)');

        $this->addSql('CREATE TABLE loan (id SERIAL NOT NULL, book_id INT NOT NULL, user_id INT NOT NULL, borrowed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, due_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, returned_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C5D30D03516A6F96 ON loan (book_id)');
        $this->addSql('CREATE INDEX IDX_C5D30D03A76ED395 ON loan (user_id)');
        $this->addSql('ALTER TABLE loan ADD CONSTRAINT FK_C5D30D03516A6F96 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE loan ADD CONSTRAINT FK_C5D30D03A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE book DROP CONSTRAINT FK_CBE5A331F675F31B');
        $this->addSql('ALTER TABLE book_category DROP CONSTRAINT FK_C745FC58516A6F96');
        $this->addSql('ALTER TABLE book_category DROP CONSTRAINT FK_C745FC5812469DE2');
        $this->addSql('ALTER TABLE loan DROP CONSTRAINT FK_C5D30D03516A6F96');
        $this->addSql('ALTER TABLE loan DROP CONSTRAINT FK_C5D30D03A76ED395');

        $this->addSql('DROP TABLE loan');
        $this->addSql('DROP TABLE book_category');
        $this->addSql('DROP TABLE book');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE author');
        $this->addSql('DROP TABLE app_user');
    }
}
