<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260327142751 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE animal (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, espece VARCHAR(255) NOT NULL, race VARCHAR(100) NOT NULL, date_naissance DATE NOT NULL, sexe VARCHAR(20) NOT NULL, photo_url VARCHAR(255) DEFAULT NULL, proprietaire_id INT NOT NULL, INDEX IDX_6AAB231F76C50E4A (proprietaire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE evenement (id INT AUTO_INCREMENT NOT NULL, date_heure_evenement DATETIME NOT NULL, statut VARCHAR(50) NOT NULL, rappel_jours_avant INT NOT NULL, rappel_actif TINYINT NOT NULL, commentaire LONGTEXT DEFAULT NULL, date_creation DATETIME NOT NULL, date_modification DATETIME DEFAULT NULL, animal_id INT NOT NULL, type_evenement_id INT NOT NULL, createur_id INT NOT NULL, INDEX IDX_B26681E8E962C16 (animal_id), INDEX IDX_B26681E88939516 (type_evenement_id), INDEX IDX_B26681E73A201E5 (createur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE partage_animal (id INT AUTO_INCREMENT NOT NULL, role_partage VARCHAR(50) NOT NULL, date_invitation DATETIME NOT NULL, animal_id INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_FEE3C15D8E962C16 (animal_id), INDEX IDX_FEE3C15DFB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE type_evenement (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, couleur VARCHAR(50) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, date_inscription DATETIME NOT NULL, role VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE animal ADD CONSTRAINT FK_6AAB231F76C50E4A FOREIGN KEY (proprietaire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681E8E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id)');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681E88939516 FOREIGN KEY (type_evenement_id) REFERENCES type_evenement (id)');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681E73A201E5 FOREIGN KEY (createur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE partage_animal ADD CONSTRAINT FK_FEE3C15D8E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id)');
        $this->addSql('ALTER TABLE partage_animal ADD CONSTRAINT FK_FEE3C15DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE animal DROP FOREIGN KEY FK_6AAB231F76C50E4A');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_B26681E8E962C16');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_B26681E88939516');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_B26681E73A201E5');
        $this->addSql('ALTER TABLE partage_animal DROP FOREIGN KEY FK_FEE3C15D8E962C16');
        $this->addSql('ALTER TABLE partage_animal DROP FOREIGN KEY FK_FEE3C15DFB88E14F');
        $this->addSql('DROP TABLE animal');
        $this->addSql('DROP TABLE evenement');
        $this->addSql('DROP TABLE partage_animal');
        $this->addSql('DROP TABLE type_evenement');
        $this->addSql('DROP TABLE `user`');
    }
}
