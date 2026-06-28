<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'User: role→roles(JSON), add photo_url; Animal: nullable race/dateNaissance/sexe; Evenement: nullable rappelJoursAvant';
    }

    public function up(Schema $schema): void
    {
        // User: role VARCHAR(50) → roles JSON, add photo_url
        $this->addSql('ALTER TABLE `user` DROP COLUMN role');
        $this->addSql('ALTER TABLE `user` ADD roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('UPDATE `user` SET roles = \'["ROLE_USER"]\'');
        $this->addSql('ALTER TABLE `user` ADD photo_url VARCHAR(500) DEFAULT NULL');

        // Animal: make race, date_naissance, sexe nullable
        $this->addSql('ALTER TABLE animal MODIFY race VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE animal MODIFY date_naissance DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE animal MODIFY sexe VARCHAR(20) DEFAULT NULL');

        // Evenement: make rappel_jours_avant nullable
        $this->addSql('ALTER TABLE evenement MODIFY rappel_jours_avant INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP COLUMN roles');
        $this->addSql('ALTER TABLE `user` DROP COLUMN photo_url');
        $this->addSql('ALTER TABLE `user` ADD role VARCHAR(50) NOT NULL');

        $this->addSql('ALTER TABLE animal MODIFY race VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE animal MODIFY date_naissance DATE NOT NULL');
        $this->addSql('ALTER TABLE animal MODIFY sexe VARCHAR(20) NOT NULL');

        $this->addSql('ALTER TABLE evenement MODIFY rappel_jours_avant INT NOT NULL');
    }
}
