<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250620231813 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user_ticket ADD responsable_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_ticket ADD CONSTRAINT FK_F2F2B69E53C59D72 FOREIGN KEY (responsable_id) REFERENCES responsable_ticket (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F2F2B69E53C59D72 ON user_ticket (responsable_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user_ticket DROP FOREIGN KEY FK_F2F2B69E53C59D72
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_F2F2B69E53C59D72 ON user_ticket
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_ticket DROP responsable_id
        SQL);
    }
}
