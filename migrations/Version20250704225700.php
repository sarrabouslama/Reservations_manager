<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250704225700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE payement ADD piscine_reservation_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payement ADD CONSTRAINT FK_B20A7885EF364438 FOREIGN KEY (piscine_reservation_id) REFERENCES piscine_reservation (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_B20A7885EF364438 ON payement (piscine_reservation_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE payement DROP FOREIGN KEY FK_B20A7885EF364438
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_B20A7885EF364438 ON payement
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payement DROP piscine_reservation_id
        SQL);
    }
}
