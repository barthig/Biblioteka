<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260324123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reconcile schema drift for loan.updated_at on databases initialized from legacy SQL dumps';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE loan ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('UPDATE loan SET updated_at = COALESCE(updated_at, returned_at, last_extended_at, borrowed_at, NOW())');
        $this->addSql('ALTER TABLE loan ALTER COLUMN updated_at SET NOT NULL');
        $this->addSql("COMMENT ON COLUMN loan.updated_at IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE loan DROP COLUMN IF EXISTS updated_at');
    }
}