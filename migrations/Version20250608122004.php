<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250608122004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_8D93D649E7927C74 ON user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD tel VARCHAR(255) DEFAULT NULL, ADD actif TINYINT(1) NOT NULL, ADD sit VARCHAR(255) NOT NULL, ADD nb_enfants INT NOT NULL, ADD emploi VARCHAR(255) NOT NULL, ADD matricule_cnss VARCHAR(255) NOT NULL, ADD direction VARCHAR(255) NOT NULL, ADD image VARCHAR(255) DEFAULT NULL, CHANGE email email VARCHAR(180) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP tel, DROP actif, DROP sit, DROP nb_enfants, DROP emploi, DROP matricule_cnss, DROP direction, DROP image, CHANGE email email VARCHAR(180) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)
        SQL);
    }
}
