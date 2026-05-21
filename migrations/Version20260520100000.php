<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260520100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add customer orders, bookings, and payments tables for Customer API';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE customer_order (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            status VARCHAR(20) NOT NULL,
            total_amount NUMERIC(12, 2) NOT NULL,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_CUSTOMER_ORDER_USER (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE customer_order_item (
            id INT AUTO_INCREMENT NOT NULL,
            customer_order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            unit_price NUMERIC(12, 2) NOT NULL,
            line_total NUMERIC(12, 2) NOT NULL,
            INDEX IDX_ORDER_ITEM_ORDER (customer_order_id),
            INDEX IDX_ORDER_ITEM_PRODUCT (product_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE customer_booking (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            scheduled_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            status VARCHAR(20) NOT NULL,
            notes LONGTEXT DEFAULT NULL,
            contact_phone VARCHAR(30) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_CUSTOMER_BOOKING_USER (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE customer_payment (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            customer_order_id INT NOT NULL,
            amount NUMERIC(12, 2) NOT NULL,
            method VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL,
            reference VARCHAR(64) NOT NULL,
            paid_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_PAYMENT_REFERENCE (reference),
            INDEX IDX_CUSTOMER_PAYMENT_USER (user_id),
            INDEX IDX_CUSTOMER_PAYMENT_ORDER (customer_order_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_CUSTOMER_ORDER_USER FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE customer_order_item ADD CONSTRAINT FK_ORDER_ITEM_ORDER FOREIGN KEY (customer_order_id) REFERENCES customer_order (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE customer_order_item ADD CONSTRAINT FK_ORDER_ITEM_PRODUCT FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE customer_booking ADD CONSTRAINT FK_CUSTOMER_BOOKING_USER FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE customer_payment ADD CONSTRAINT FK_CUSTOMER_PAYMENT_USER FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE customer_payment ADD CONSTRAINT FK_CUSTOMER_PAYMENT_ORDER FOREIGN KEY (customer_order_id) REFERENCES customer_order (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_payment DROP FOREIGN KEY FK_CUSTOMER_PAYMENT_ORDER');
        $this->addSql('ALTER TABLE customer_payment DROP FOREIGN KEY FK_CUSTOMER_PAYMENT_USER');
        $this->addSql('ALTER TABLE customer_booking DROP FOREIGN KEY FK_CUSTOMER_BOOKING_USER');
        $this->addSql('ALTER TABLE customer_order_item DROP FOREIGN KEY FK_ORDER_ITEM_PRODUCT');
        $this->addSql('ALTER TABLE customer_order_item DROP FOREIGN KEY FK_ORDER_ITEM_ORDER');
        $this->addSql('ALTER TABLE customer_order DROP FOREIGN KEY FK_CUSTOMER_ORDER_USER');
        $this->addSql('DROP TABLE customer_payment');
        $this->addSql('DROP TABLE customer_booking');
        $this->addSql('DROP TABLE customer_order_item');
        $this->addSql('DROP TABLE customer_order');
    }
}
