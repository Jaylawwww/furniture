<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260319090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email verification token fields to user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD verification_token VARCHAR(64) DEFAULT NULL, ADD verification_token_expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP verification_token, DROP verification_token_expires_at');
    }
}

