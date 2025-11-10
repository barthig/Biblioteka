<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251110160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Introduce access types for book copies, track storage/open availability, and tighten order pickup types.';
    }

    public function up(Schema $schema): void
    {
        // Book copies access type classification
        $this->addSql("ALTER TABLE book_copy ADD access_type VARCHAR(30) DEFAULT 'STORAGE' NOT NULL");
        $this->addSql("UPDATE book_copy SET access_type = 'STORAGE' WHERE access_type IS NULL");

        // Persist aggregated availability counters on books
        $this->addSql('ALTER TABLE book ADD storage_copies INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE book ADD open_stack_copies INT NOT NULL DEFAULT 0');
        $this->addSql('UPDATE book SET storage_copies = copies, open_stack_copies = 0');

        // Extend pickup type metadata for orders
        $this->addSql("ALTER TABLE order_request ALTER COLUMN pickup_type TYPE VARCHAR(30)");
        $this->addSql("UPDATE order_request SET pickup_type = 'STORAGE_DESK' WHERE pickup_type IS NULL OR pickup_type = ''");
        $this->addSql("ALTER TABLE order_request ALTER COLUMN pickup_type SET DEFAULT 'STORAGE_DESK'");
        $this->addSql("ALTER TABLE order_request ALTER COLUMN pickup_type SET NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE order_request ALTER COLUMN pickup_type DROP DEFAULT");
        $this->addSql("ALTER TABLE order_request ALTER COLUMN pickup_type DROP NOT NULL");
        $this->addSql("ALTER TABLE order_request ALTER COLUMN pickup_type TYPE VARCHAR(20)");

        $this->addSql('ALTER TABLE book DROP COLUMN open_stack_copies');
        $this->addSql('ALTER TABLE book DROP COLUMN storage_copies');

        $this->addSql('ALTER TABLE book_copy DROP COLUMN access_type');
    }
}
