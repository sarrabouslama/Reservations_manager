<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250623112329 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE user_ticket DROP FOREIGN KEY FK_F2F2B69E700047D2");
        $this->addSql("DROP INDEX IDX_F2F2B69E700047D2 ON user_ticket");

        $this->addSql(<<<'SQL'
            ALTER TABLE user_ticket DROP ticket_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE responsable_ticket DROP INDEX IDX_347D3CF253C59D72, ADD UNIQUE INDEX UNIQ_347D3CF253C59D72 (responsable_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE responsable_ticket DROP FOREIGN KEY FK_347D3CF2700047D2
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_347D3CF2700047D2 ON responsable_ticket
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE responsable_ticket DROP ticket_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_ticket ADD ticket_id INT NOT NULL
        SQL);
    }
}
