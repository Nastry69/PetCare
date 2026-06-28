<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute rappel_envoye (BOOLEAN NOT NULL DEFAULT FALSE) sur evenement.
 *
 * Ce champ empêche les doublons de rappels : le scheduler tourne chaque minute
 * et doit n'envoyer l'email qu'une seule fois par événement.
 */
final class Version20260526000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Evenement : ajout de rappel_envoye pour les rappels à l\'heure exacte du RDV';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE evenement ADD rappel_envoye BOOLEAN NOT NULL DEFAULT FALSE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE evenement DROP COLUMN rappel_envoye');
    }
}
