<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250704121007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE piscine (id INT AUTO_INCREMENT NOT NULL, region VARCHAR(255) NOT NULL, hotel VARCHAR(255) NOT NULL, prix_initial DOUBLE PRECISION NOT NULL, consommation DOUBLE PRECISION NOT NULL, amicale DOUBLE PRECISION NOT NULL, prix_final DOUBLE PRECISION NOT NULL, avance DOUBLE PRECISION NOT NULL, date_limite DATE DEFAULT NULL, entrée TIME DEFAULT NULL, sortie TIME DEFAULT NULL, nb_enfants INT NOT NULL, nb_adultes INT NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE piscine
        SQL);
    }
}
