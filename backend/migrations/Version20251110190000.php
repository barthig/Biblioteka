<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251110190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'User membership metadata, acquisitions (suppliers, orders, budgets), and weeding records.';
    }

    public function up(Schema $schema): void
    {
        // Extend app_user with membership controls
        $this->addSql("ALTER TABLE app_user ADD COLUMN IF NOT EXISTS blocked BOOLEAN DEFAULT false NOT NULL");
        $this->addSql("ALTER TABLE app_user ADD COLUMN IF NOT EXISTS membership_group VARCHAR(64) DEFAULT 'standard' NOT NULL");
        $this->addSql("ALTER TABLE app_user ADD COLUMN IF NOT EXISTS loan_limit INT DEFAULT 5 NOT NULL");
        $this->addSql("ALTER TABLE app_user ADD COLUMN IF NOT EXISTS blocked_reason VARCHAR(255) DEFAULT NULL");

        // Supplier table
        $this->addSql('CREATE TABLE IF NOT EXISTS supplier (
            id INT GENERATED ALWAYS AS IDENTITY NOT NULL,
            name VARCHAR(180) NOT NULL,
            contact_email VARCHAR(180) DEFAULT NULL,
            contact_phone VARCHAR(60) DEFAULT NULL,
            address_line VARCHAR(255) DEFAULT NULL,
            city VARCHAR(120) DEFAULT NULL,
            country VARCHAR(120) DEFAULT NULL,
            tax_identifier VARCHAR(60) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            active BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');

        // Acquisition budget
        $this->addSql('CREATE TABLE IF NOT EXISTS acquisition_budget (
            id INT GENERATED ALWAYS AS IDENTITY NOT NULL,
            name VARCHAR(160) NOT NULL,
            fiscal_year VARCHAR(9) NOT NULL,
            allocated_amount NUMERIC(12, 2) NOT NULL,
            spent_amount NUMERIC(12, 2) NOT NULL,
            currency VARCHAR(3) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');

        // Acquisition order
        $this->addSql('CREATE TABLE IF NOT EXISTS acquisition_order (
            id INT GENERATED ALWAYS AS IDENTITY NOT NULL,
            supplier_id INT NOT NULL,
            budget_id INT DEFAULT NULL,
            reference_number VARCHAR(120) DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            items JSON DEFAULT NULL,
            total_amount NUMERIC(12, 2) NOT NULL,
            currency VARCHAR(3) NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            ordered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            expected_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            received_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            cancelled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_ACQ_ORDER_SUPPLIER ON acquisition_order (supplier_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_ACQ_ORDER_BUDGET ON acquisition_order (budget_id)');
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE lower(conname) = 'fk_acq_order_supplier'
    ) THEN
        ALTER TABLE acquisition_order ADD CONSTRAINT FK_ACQ_ORDER_SUPPLIER FOREIGN KEY (supplier_id) REFERENCES supplier (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
    END IF;
END $$;
SQL);
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE lower(conname) = 'fk_acq_order_budget'
    ) THEN
        ALTER TABLE acquisition_order ADD CONSTRAINT FK_ACQ_ORDER_BUDGET FOREIGN KEY (budget_id) REFERENCES acquisition_budget (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE;
    END IF;
END $$;
SQL);

        // Acquisition expense
        $this->addSql('CREATE TABLE IF NOT EXISTS acquisition_expense (
            id INT GENERATED ALWAYS AS IDENTITY NOT NULL,
            budget_id INT NOT NULL,
            order_id INT DEFAULT NULL,
            amount NUMERIC(12, 2) NOT NULL,
            currency VARCHAR(3) NOT NULL,
            description VARCHAR(255) NOT NULL,
            type VARCHAR(20) NOT NULL,
            posted_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_ACQ_EXPENSE_BUDGET ON acquisition_expense (budget_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_ACQ_EXPENSE_ORDER ON acquisition_expense (order_id)');
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE lower(conname) = 'fk_acq_expense_budget'
    ) THEN
        ALTER TABLE acquisition_expense ADD CONSTRAINT FK_ACQ_EXPENSE_BUDGET FOREIGN KEY (budget_id) REFERENCES acquisition_budget (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
    END IF;
END $$;
SQL);
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE lower(conname) = 'fk_acq_expense_order'
    ) THEN
        ALTER TABLE acquisition_expense ADD CONSTRAINT FK_ACQ_EXPENSE_ORDER FOREIGN KEY (order_id) REFERENCES acquisition_order (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE;
    END IF;
END $$;
SQL);

        // Weeding records
        $this->addSql('CREATE TABLE IF NOT EXISTS weeding_record (
            id INT GENERATED ALWAYS AS IDENTITY NOT NULL,
            book_id INT NOT NULL,
            book_copy_id INT DEFAULT NULL,
            processed_by_id INT DEFAULT NULL,
            reason VARCHAR(255) NOT NULL,
            action VARCHAR(20) NOT NULL,
            condition_state VARCHAR(120) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            removed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_WEEDING_BOOK ON weeding_record (book_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_WEEDING_COPY ON weeding_record (book_copy_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_WEEDING_USER ON weeding_record (processed_by_id)');
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE lower(conname) = 'fk_weeding_book'
    ) THEN
        ALTER TABLE weeding_record ADD CONSTRAINT FK_WEEDING_BOOK FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
    END IF;
END $$;
SQL);
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE lower(conname) = 'fk_weeding_copy'
    ) THEN
        ALTER TABLE weeding_record ADD CONSTRAINT FK_WEEDING_COPY FOREIGN KEY (book_copy_id) REFERENCES book_copy (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE;
    END IF;
END $$;
SQL);
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE lower(conname) = 'fk_weeding_user'
    ) THEN
        ALTER TABLE weeding_record ADD CONSTRAINT FK_WEEDING_USER FOREIGN KEY (processed_by_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE;
    END IF;
END $$;
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS weeding_record CASCADE');
        $this->addSql('DROP TABLE IF EXISTS acquisition_expense CASCADE');
        $this->addSql('DROP TABLE IF EXISTS acquisition_order CASCADE');
        $this->addSql('DROP TABLE IF EXISTS acquisition_budget CASCADE');
        $this->addSql('DROP TABLE IF EXISTS supplier CASCADE');

        $this->addSql('ALTER TABLE app_user DROP COLUMN IF EXISTS blocked');
        $this->addSql('ALTER TABLE app_user DROP COLUMN IF EXISTS membership_group');
        $this->addSql('ALTER TABLE app_user DROP COLUMN IF EXISTS loan_limit');
        $this->addSql('ALTER TABLE app_user DROP COLUMN IF EXISTS blocked_reason');
    }
}
