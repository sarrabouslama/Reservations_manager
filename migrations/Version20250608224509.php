<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250608224509 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE home_image (id INT AUTO_INCREMENT NOT NULL, home_id INT NOT NULL, filename VARCHAR(255) NOT NULL, INDEX IDX_CEC7FDCF28CDC89C (home_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE home_image ADD CONSTRAINT FK_CEC7FDCF28CDC89C FOREIGN KEY (home_id) REFERENCES home (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE home DROP image
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE home_image DROP FOREIGN KEY FK_CEC7FDCF28CDC89C
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE home_image
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE home ADD image VARCHAR(255) DEFAULT NULL
        SQL);
    }
}
