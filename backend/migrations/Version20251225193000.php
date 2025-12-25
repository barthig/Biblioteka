<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251225193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add location to announcement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE announcement ADD location VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE announcement DROP location');
    }
}
