<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251220160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add event_at to announcement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE announcement ADD event_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE announcement DROP event_at');
    }
}
