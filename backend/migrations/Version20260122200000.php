<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260122200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add PREPARED status and preparedAt field to reservations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation ADD prepared_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE reservation MODIFY status VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation DROP prepared_at');
    }
}
