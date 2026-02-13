<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * S-11: Increase PIN column length to store bcrypt hash instead of plaintext.
 */
final class Version20260210100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Increase user.pin column length from 4 to 255 to store hashed PINs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ALTER COLUMN pin TYPE VARCHAR(255)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ALTER COLUMN pin TYPE VARCHAR(4)');
    }
}
