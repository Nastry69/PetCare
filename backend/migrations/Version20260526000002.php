<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Données initiales : types d'événements PetCare.
 *
 * Insère les 5 types de base uniquement si la table est vide (idempotent).
 * Chaque INSERT utilise WHERE NOT EXISTS pour éviter les doublons en cas
 * de ré-exécution partielle.
 */
final class Version20260526000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed: types d\'événements par défaut (Vaccin, Traitement, Toilettage, Consultation, Autre)';
    }

    public function up(Schema $schema): void
    {
        // Données de base — insérées uniquement si absentes (idempotent)
        $types = [
            ['Vaccin',       'Vaccination et rappels vaccinaux',                         '#1377EC'],
            ['Traitement',   'Traitements médicaux, antiparasitaires ou médicaments',    '#22C55E'],
            ['Toilettage',   'Toilettage, bain, coupe ou entretien',                     '#F59E0B'],
            ['Consultation', 'Consultation vétérinaire ou contrôle de santé',            '#EF4444'],
            ['Autre',        'Autre événement non catégorisé',                           '#94A3B8'],
        ];

        foreach ($types as [$libelle, $description, $couleur]) {
            // Compatible PostgreSQL et SQLite (tests)
            $this->addSql(
                "INSERT INTO type_evenement (libelle, description, couleur)
                 SELECT :libelle, :description, :couleur
                 WHERE NOT EXISTS (
                     SELECT 1 FROM type_evenement WHERE libelle = :libelle
                 )",
                [
                    'libelle'     => $libelle,
                    'description' => $description,
                    'couleur'     => $couleur,
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Suppression des types insérés par cette migration
        $this->addSql(
            "DELETE FROM type_evenement WHERE libelle IN ('Vaccin', 'Traitement', 'Toilettage', 'Consultation', 'Autre')"
        );
    }
}
