<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250624113337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE payement ADD reservation_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payement ADD CONSTRAINT FK_B20A7885B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id) ON DELETE SET NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_B20A7885B83297E7 ON payement (reservation_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955868C0609
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_42C84955868C0609 ON reservation
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP payement_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE payement DROP FOREIGN KEY FK_B20A7885B83297E7
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_B20A7885B83297E7 ON payement
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payement DROP reservation_id, DROP date_saisie
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD payement_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT FK_42C84955868C0609 FOREIGN KEY (payement_id) REFERENCES payement (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_42C84955868C0609 ON reservation (payement_id)
        SQL);
    }
}
