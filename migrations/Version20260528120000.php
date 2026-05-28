<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add customer_device_token table for FCM push notifications';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE customer_device_token (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            platform VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX uniq_customer_device_token (token),
            INDEX IDX_CUSTOMER_DEVICE_TOKEN_USER (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE customer_device_token ADD CONSTRAINT FK_CUSTOMER_DEVICE_TOKEN_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_device_token DROP FOREIGN KEY FK_CUSTOMER_DEVICE_TOKEN_USER');
        $this->addSql('DROP TABLE customer_device_token');
    }
}

