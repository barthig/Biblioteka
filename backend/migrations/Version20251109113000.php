<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251109113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Extend library model with book copies, reservations, fines and user contact details';
    }

    public function up(Schema $schema): void
    {
        // create book_copy table
        $this->addSql('CREATE TABLE book_copy (id SERIAL NOT NULL, book_id INT NOT NULL, inventory_code VARCHAR(60) NOT NULL, status VARCHAR(20) NOT NULL, location VARCHAR(120) DEFAULT NULL, condition_state VARCHAR(120) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3F3DC7324B59C89F ON book_copy (inventory_code)');
        $this->addSql('CREATE INDEX IDX_3F3DC73216A6F96 ON book_copy (book_id)');
        $this->addSql("ALTER TABLE book_copy ADD CONSTRAINT FK_3F3DC73216A6F96 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");

        // extend loan with book_copy reference
        $this->addSql('ALTER TABLE loan ADD book_copy_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_C5D30D03EC5D2767 ON loan (book_copy_id)');
        $this->addSql("ALTER TABLE loan ADD CONSTRAINT FK_C5D30D03EC5D2767 FOREIGN KEY (book_copy_id) REFERENCES book_copy (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE");

        // reservation table
        $this->addSql('CREATE TABLE reservation (id SERIAL NOT NULL, book_id INT NOT NULL, book_copy_id INT DEFAULT NULL, user_id INT NOT NULL, status VARCHAR(20) NOT NULL, reserved_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, fulfilled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, cancelled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_42C8495516A6F96 ON reservation (book_id)');
        $this->addSql('CREATE INDEX IDX_42C84955EC5D2767 ON reservation (book_copy_id)');
        $this->addSql('CREATE INDEX IDX_42C84955A76ED395 ON reservation (user_id)');
        $this->addSql("ALTER TABLE reservation ADD CONSTRAINT FK_42C8495516A6F96 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE reservation ADD CONSTRAINT FK_42C84955EC5D2767 FOREIGN KEY (book_copy_id) REFERENCES book_copy (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");

        // fine table
        $this->addSql('CREATE TABLE fine (id SERIAL NOT NULL, loan_id INT NOT NULL, amount NUMERIC(8, 2) NOT NULL, currency VARCHAR(3) NOT NULL, reason VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8C0455542B9AFC9 ON fine (loan_id)');
        $this->addSql("ALTER TABLE fine ADD CONSTRAINT FK_8C0455542B9AFC9 FOREIGN KEY (loan_id) REFERENCES loan (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");

        // user contact columns
        $this->addSql('ALTER TABLE app_user ADD phone_number VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD address_line VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD city VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD postal_code VARCHAR(12) DEFAULT NULL');

        // populate book_copy table based on current inventory counts
        $this->addSql(<<<'SQL'
DO $$
DECLARE
    rec RECORD;
    copy_index INTEGER;
    available_count INTEGER;
    total_count INTEGER;
    inserted_id INTEGER;
BEGIN
    FOR rec IN SELECT id, total_copies, copies FROM book LOOP
        IF rec.total_copies IS NULL OR rec.total_copies <= 0 THEN
            CONTINUE;
        END IF;
        available_count := GREATEST(rec.copies, 0);
        total_count := rec.total_copies;
        FOR copy_index IN 1..total_count LOOP
            INSERT INTO book_copy (book_id, inventory_code, status, created_at, updated_at)
            VALUES (
                rec.id,
                format('B%1$05d-%2$03d', rec.id, copy_index),
                CASE WHEN copy_index <= available_count THEN 'AVAILABLE' ELSE 'BORROWED' END,
                NOW(),
                NOW()
            )
            RETURNING id INTO inserted_id;
        END LOOP;
    END LOOP;
END $$;
SQL);

        // assign borrowed copies to existing loans where possible
        $this->addSql(<<<'SQL'
DO $$
DECLARE
    loan_rec RECORD;
    copy_rec RECORD;
BEGIN
    FOR loan_rec IN SELECT id, book_id FROM loan ORDER BY borrowed_at LOOP
        SELECT id
        INTO copy_rec
        FROM book_copy
        WHERE book_id = loan_rec.book_id
          AND status = 'BORROWED'
          AND id NOT IN (SELECT COALESCE(book_copy_id, 0) FROM loan WHERE book_copy_id IS NOT NULL)
        ORDER BY id
        LIMIT 1;

        IF copy_rec.id IS NULL THEN
            SELECT id INTO copy_rec FROM book_copy WHERE book_id = loan_rec.book_id ORDER BY id LIMIT 1;
        END IF;

        IF copy_rec.id IS NOT NULL THEN
            UPDATE loan SET book_copy_id = copy_rec.id WHERE id = loan_rec.id;
        END IF;
    END LOOP;
END $$;
SQL);

        // refresh availability counters
        $this->addSql(<<<'SQL'
WITH counts AS (
    SELECT book_id,
           COUNT(*) AS total_cnt,
           COUNT(*) FILTER (WHERE status = 'AVAILABLE') AS available_cnt
    FROM book_copy
    GROUP BY book_id
)
UPDATE book b
SET total_copies = c.total_cnt,
    copies = c.available_cnt
FROM counts c
WHERE c.book_id = b.id
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fine DROP CONSTRAINT FK_8C0455542B9AFC9');
        $this->addSql('ALTER TABLE reservation DROP CONSTRAINT FK_42C8495516A6F96');
        $this->addSql('ALTER TABLE reservation DROP CONSTRAINT FK_42C84955EC5D2767');
        $this->addSql('ALTER TABLE reservation DROP CONSTRAINT FK_42C84955A76ED395');
        $this->addSql('ALTER TABLE loan DROP CONSTRAINT FK_C5D30D03EC5D2767');
        $this->addSql('ALTER TABLE book_copy DROP CONSTRAINT FK_3F3DC73216A6F96');

        $this->addSql('DROP TABLE fine');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE book_copy');

        $this->addSql('DROP INDEX IDX_C5D30D03EC5D2767');
        $this->addSql('ALTER TABLE loan DROP COLUMN book_copy_id');

        $this->addSql('ALTER TABLE app_user DROP COLUMN phone_number');
        $this->addSql('ALTER TABLE app_user DROP COLUMN address_line');
        $this->addSql('ALTER TABLE app_user DROP COLUMN city');
        $this->addSql('ALTER TABLE app_user DROP COLUMN postal_code');
    }
}
