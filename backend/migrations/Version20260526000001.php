<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute les colonnes de réinitialisation de mot de passe par token sécurisé.
 *
 * reset_token           — token hexadécimal (64 chars) à durée limitée
 * reset_token_expires_at — expiration du token (1 heure après génération)
 */
final class Version20260526000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'User: add reset_token and reset_token_expires_at for secure password reset';
    }

    public function up(Schema $schema): void
    {
        // Platform-independent via Doctrine Schema API
        $table = $schema->getTable('user');

        if (!$table->hasColumn('reset_token')) {
            $table->addColumn('reset_token', 'string', [
                'length'  => 100,
                'notnull' => false,
                'default' => null,
            ]);
        }

        if (!$table->hasColumn('reset_token_expires_at')) {
            $table->addColumn('reset_token_expires_at', 'datetime_immutable', [
                'notnull' => false,
                'default' => null,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('user');

        if ($table->hasColumn('reset_token')) {
            $table->dropColumn('reset_token');
        }

        if ($table->hasColumn('reset_token_expires_at')) {
            $table->dropColumn('reset_token_expires_at');
        }
    }
}
