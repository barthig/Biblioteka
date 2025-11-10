<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251110153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add expired_at column for pickup orders.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('order_request');
        if (!$table->hasColumn('expired_at')) {
            $table->addColumn('expired_at', 'datetime_immutable', ['notnull' => false]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('order_request');
        if ($table->hasColumn('expired_at')) {
            $table->dropColumn('expired_at');
        }
    }
}
