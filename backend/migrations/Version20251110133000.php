<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251110133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pickup orders, favorites, reviews tables and loan extension metadata';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('order_request')) {
            $order = $schema->createTable('order_request');
            $order->addColumn('id', 'integer', ['autoincrement' => true]);
            $order->addColumn('book_id', 'integer');
            $order->addColumn('book_copy_id', 'integer', ['notnull' => false]);
            $order->addColumn('user_id', 'integer');
            $order->addColumn('status', 'string', ['length' => 20]);
            $order->addColumn('pickup_type', 'string', ['length' => 20, 'notnull' => false]);
            $order->addColumn('created_at', 'datetime_immutable');
            $order->addColumn('pickup_deadline', 'datetime_immutable', ['notnull' => false]);
            $order->addColumn('cancelled_at', 'datetime_immutable', ['notnull' => false]);
            $order->addColumn('collected_at', 'datetime_immutable', ['notnull' => false]);
            $order->setPrimaryKey(['id']);
            $order->addIndex(['book_id'], 'IDX_ORDER_REQUEST_BOOK');
            $order->addIndex(['book_copy_id'], 'IDX_ORDER_REQUEST_COPY');
            $order->addIndex(['user_id'], 'IDX_ORDER_REQUEST_USER');
            $order->addForeignKeyConstraint('book', ['book_id'], ['id'], ['onDelete' => 'CASCADE']);
            $order->addForeignKeyConstraint('book_copy', ['book_copy_id'], ['id'], ['onDelete' => 'SET NULL']);
            $order->addForeignKeyConstraint('app_user', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
        }

        if (!$schema->hasTable('favorite')) {
            $favorite = $schema->createTable('favorite');
            $favorite->addColumn('id', 'integer', ['autoincrement' => true]);
            $favorite->addColumn('user_id', 'integer');
            $favorite->addColumn('book_id', 'integer');
            $favorite->addColumn('created_at', 'datetime_immutable');
            $favorite->setPrimaryKey(['id']);
            $favorite->addIndex(['user_id'], 'IDX_FAVORITE_USER');
            $favorite->addIndex(['book_id'], 'IDX_FAVORITE_BOOK');
            $favorite->addUniqueIndex(['user_id', 'book_id'], 'favorite_user_book_unique');
            $favorite->addForeignKeyConstraint('app_user', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
            $favorite->addForeignKeyConstraint('book', ['book_id'], ['id'], ['onDelete' => 'CASCADE']);
        }

        if (!$schema->hasTable('review')) {
            $review = $schema->createTable('review');
            $review->addColumn('id', 'integer', ['autoincrement' => true]);
            $review->addColumn('book_id', 'integer');
            $review->addColumn('user_id', 'integer');
            $review->addColumn('rating', 'smallint');
            $review->addColumn('comment', 'text', ['notnull' => false]);
            $review->addColumn('created_at', 'datetime_immutable');
            $review->addColumn('updated_at', 'datetime_immutable');
            $review->setPrimaryKey(['id']);
            $review->addIndex(['book_id'], 'IDX_REVIEW_BOOK');
            $review->addIndex(['user_id'], 'IDX_REVIEW_USER');
            $review->addUniqueIndex(['user_id', 'book_id'], 'review_user_book_unique');
            $review->addForeignKeyConstraint('book', ['book_id'], ['id'], ['onDelete' => 'CASCADE']);
            $review->addForeignKeyConstraint('app_user', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
        }

        if ($schema->hasTable('loan')) {
            $loan = $schema->getTable('loan');
            if (!$loan->hasColumn('extensions_count')) {
                $loan->addColumn('extensions_count', 'integer', ['default' => 0]);
            }
            if (!$loan->hasColumn('last_extended_at')) {
                $loan->addColumn('last_extended_at', 'datetime', ['notnull' => false]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('review')) {
            $schema->dropTable('review');
        }

        if ($schema->hasTable('favorite')) {
            $schema->dropTable('favorite');
        }

        if ($schema->hasTable('order_request')) {
            $schema->dropTable('order_request');
        }

        if ($schema->hasTable('loan')) {
            $loan = $schema->getTable('loan');
            if ($loan->hasColumn('extensions_count')) {
                $loan->dropColumn('extensions_count');
            }
            if ($loan->hasColumn('last_extended_at')) {
                $loan->dropColumn('last_extended_at');
            }
        }
    }
}
