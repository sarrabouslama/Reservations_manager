<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250619172213 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE payement (id INT AUTO_INCREMENT NOT NULL, reservation_id INT NOT NULL, montant_global DOUBLE PRECISION NOT NULL, avance DOUBLE PRECISION NOT NULL, nb_mois INT NOT NULL, mode_echéance VARCHAR(255) DEFAULT NULL, code_opposition VARCHAR(255) DEFAULT NULL, date_debut DATE DEFAULT NULL, UNIQUE INDEX UNIQ_B20A7885B83297E7 (reservation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payement ADD CONSTRAINT FK_B20A7885B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE payement DROP FOREIGN KEY FK_B20A7885B83297E7
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE payement
        SQL);
    }
}
