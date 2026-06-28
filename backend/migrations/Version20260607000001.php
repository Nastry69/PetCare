<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute la table invitation_en_attente pour les partages vers des emails sans compte PetCare.
 */
final class Version20260607000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de invitation_en_attente pour les invitations vers des emails non inscrits';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS invitation_en_attente (
            id SERIAL NOT NULL,
            animal_id INT NOT NULL,
            email VARCHAR(180) NOT NULL,
            role_partage VARCHAR(50) NOT NULL,
            token VARCHAR(64) NOT NULL,
            expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_INVITATION_TOKEN ON invitation_en_attente (token)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_INVITATION_ANIMAL ON invitation_en_attente (animal_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_INVITATION_EMAIL ON invitation_en_attente (email)');
        $this->addSql('ALTER TABLE invitation_en_attente DROP CONSTRAINT IF EXISTS FK_INVITATION_ANIMAL');
        $this->addSql('ALTER TABLE invitation_en_attente ADD CONSTRAINT FK_INVITATION_ANIMAL FOREIGN KEY (animal_id) REFERENCES animal (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invitation_en_attente DROP CONSTRAINT IF EXISTS FK_INVITATION_ANIMAL');
        $this->addSql('DROP TABLE IF EXISTS invitation_en_attente');
    }
}
