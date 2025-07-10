<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250704133135 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE piscine_reservation (id INT AUTO_INCREMENT NOT NULL, piscine_id INT NOT NULL, user_id INT NOT NULL, date_reservation DATE NOT NULL, selected TINYINT(1) NOT NULL, confirmed TINYINT(1) NOT NULL, date_selection DATE DEFAULT NULL, INDEX IDX_9DDFC8CAE18396B7 (piscine_id), UNIQUE INDEX UNIQ_9DDFC8CAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE piscine_reservation ADD CONSTRAINT FK_9DDFC8CAE18396B7 FOREIGN KEY (piscine_id) REFERENCES piscine (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE piscine_reservation ADD CONSTRAINT FK_9DDFC8CAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE piscine_reservation DROP FOREIGN KEY FK_9DDFC8CAE18396B7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE piscine_reservation DROP FOREIGN KEY FK_9DDFC8CAA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE piscine_reservation
        SQL);
    }
}
