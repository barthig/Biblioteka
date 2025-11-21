<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121124500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add newsletter_subscribed flag to app_user.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('app_user');
        if (!$table->hasColumn('newsletter_subscribed')) {
            $table->addColumn('newsletter_subscribed', 'boolean', [
                'default' => true,
                'notnull' => true,
            ]);
        }

        $this->addSql('UPDATE app_user SET newsletter_subscribed = TRUE WHERE newsletter_subscribed IS NULL');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('app_user');
        if ($table->hasColumn('newsletter_subscribed')) {
            $table->dropColumn('newsletter_subscribed');
        }
    }
}
