<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260308090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add username field to user entity and unique constraint';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD username VARCHAR(50) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME ON user (username)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_USERNAME ON user');
        $this->addSql('ALTER TABLE user DROP username');
    }
}

