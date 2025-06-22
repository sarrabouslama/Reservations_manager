<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250620172944 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE responsable_ticket (id INT AUTO_INCREMENT NOT NULL, responsable_id INT NOT NULL, qte INT NOT NULL, qte_vente INT NOT NULL, total_vente DOUBLE PRECISION NOT NULL, UNIQUE INDEX UNIQ_347D3CF253C59D72 (responsable_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_ticket (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, nombre INT NOT NULL, prix_unitaire DOUBLE PRECISION NOT NULL, avance DOUBLE PRECISION NOT NULL, total DOUBLE PRECISION NOT NULL, UNIQUE INDEX UNIQ_F2F2B69EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE responsable_ticket ADD CONSTRAINT FK_347D3CF253C59D72 FOREIGN KEY (responsable_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_ticket ADD CONSTRAINT FK_F2F2B69EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ticket ADD qte INT NOT NULL, ADD prix_unitaire DOUBLE PRECISION NOT NULL, ADD total_vente DOUBLE PRECISION NOT NULL, ADD qte_vente INT NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE responsable_ticket DROP FOREIGN KEY FK_347D3CF253C59D72
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_ticket DROP FOREIGN KEY FK_F2F2B69EA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE responsable_ticket
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_ticket
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ticket DROP qte, DROP prix_unitaire, DROP total_vente, DROP qte_vente
        SQL);
    }
}
