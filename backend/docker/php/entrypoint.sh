#!/bin/bash
# ================================================================
# PetCare — Entrypoint conteneur PHP
# ================================================================
# Exécuté au démarrage du conteneur app ET worker. Effectue :
#   1. Génération des clés JWT (si absentes)
#   2. Permissions dossiers d'upload
#   3. Nettoyage du cache  ← skippé si WORKER_MODE=1
#   4. Initialisation BDD  ← skippé si WORKER_MODE=1
#   5. Warmup du cache     ← skippé si WORKER_MODE=1
#   6. Démarrage du processus (php-fpm par défaut, ou CMD passé)
#
# Le service worker utilise WORKER_MODE=1 pour sauter les étapes
# BDD/cache (déjà gérées par le conteneur app).
# ================================================================
set -e

if [ ! -f /app/.env ]; then
    printf "APP_ENV=%s\nAPP_SECRET=%s\n" "${APP_ENV:-prod}" "${APP_SECRET:-}" > /app/.env
fi

APP_ENV="${APP_ENV:-prod}"
WORKER_MODE="${WORKER_MODE:-0}"

echo ""
echo "╔══════════════════════════════════════╗"
echo "║         PetCare — Démarrage          ║"
echo "╚══════════════════════════════════════╝"

# ── 1. Clés JWT ──────────────────────────────────────────────────
if [ ! -f /app/config/jwt/private.pem ]; then
    echo ""
    echo "🔑  Génération des clés JWT..."
    mkdir -p /app/config/jwt

    openssl genpkey \
        -algorithm RSA \
        -out /app/config/jwt/private.pem \
        -aes256 \
        -pass "pass:${JWT_PASSPHRASE}" \
        -pkeyopt rsa_keygen_bits:4096 2>/dev/null

    openssl rsa \
        -in /app/config/jwt/private.pem \
        -passin "pass:${JWT_PASSPHRASE}" \
        -out /app/config/jwt/public.pem \
        -pubout 2>/dev/null

    chmod 600 /app/config/jwt/private.pem
    chmod 644 /app/config/jwt/public.pem

    echo "✅  Clés JWT générées."
else
    echo "✅  Clés JWT déjà présentes."
fi

# Permissions JWT — toujours appliquées (PHP-FPM = www-data, entrypoint = root)
chmod 777 /app/config/jwt/private.pem 2>/dev/null || true
chmod 777 /app/config/jwt/public.pem  2>/dev/null || true

# ── 2. Permissions dossiers d'upload ────────────────────────────
echo ""
echo "📁  Initialisation des dossiers d'upload..."
mkdir -p /app/public/uploads/animals /app/public/uploads/users
chmod -R 777 /app/public/uploads
echo "✅  Dossiers d'upload prêts."

if [ "$WORKER_MODE" = "0" ]; then
    # ── 3. Nettoyage du cache ─────────────────────────────────────
    echo ""
    echo "🧹  Nettoyage du cache..."
    rm -rf /app/var/cache/*
    echo "✅  Cache nettoyé."

    # ── 4. Base de données ────────────────────────────────────────
    echo ""
    echo "🗄️   Initialisation de la base de données..."

    # Vérification directe via PDO (sans Symfony, résistant au cache vide)
    DB_INITIALIZED=false
    if php -r "
    try {
        \$url = getenv('DATABASE_URL');
        preg_match('#postgresql://([^:]+):([^@]+)@([^:/]+)[:/](\d+)/([^?]+)#', \$url, \$m);
        \$pdo = new PDO('pgsql:host='.\$m[3].';port='.\$m[4].';dbname='.\$m[5], \$m[1], \$m[2]);
        \$r = \$pdo->query('SELECT 1 FROM doctrine_migration_versions LIMIT 1');
        exit(\$r !== false ? 0 : 1);
    } catch (Exception \$e) { exit(1); }
    " 2>/dev/null; then
        DB_INITIALIZED=true
    fi

    if [ "$DB_INITIALIZED" = "false" ]; then
        echo "📦  Première installation — création du schéma depuis les entités..."
        php bin/console doctrine:schema:create --no-interaction --env="${APP_ENV}"

        echo "🔖  Initialisation de la table de suivi des migrations..."
        php bin/console doctrine:migrations:sync-metadata-storage \
            --no-interaction --env="${APP_ENV}" 2>/dev/null || true

        echo "🔖  Marquage des migrations de schéma comme déjà appliquées..."
        for version in \
            'DoctrineMigrations\Version20260327142751' \
            'DoctrineMigrations\Version20260502000001' \
            'DoctrineMigrations\Version20260526000001' \
            'DoctrineMigrations\Version20260526000003' \
            'DoctrineMigrations\Version20260607000001'; do
            php bin/console doctrine:migrations:version \
                "$version" --add --no-interaction --env="${APP_ENV}" || true
        done
        # NB: Version20260526000002 (seed types événements) est volontairement
        # exclue pour qu'elle s'exécute réellement via doctrine:migrations:migrate

        echo "✅  Schéma créé."
    fi

    echo "🔄  Application des migrations Doctrine..."
    php bin/console doctrine:migrations:migrate \
        --no-interaction \
        --allow-no-migration \
        --env="${APP_ENV}"
    echo "✅  Base de données prête."

    # ── 5. Cache Symfony (prod uniquement) ───────────────────────
    if [ "$APP_ENV" = "prod" ]; then
        echo ""
        echo "🗃️   Warmup du cache Symfony (prod)..."
        php bin/console cache:warmup --env=prod || echo "⚠️  Warmup échoué — le cache sera reconstruit à la première requête."
        echo "✅  Cache prêt."
    fi
else
    echo ""
    echo "⚙️   Mode worker — setup DB/cache skippé (géré par le conteneur app)."
    # Courte attente pour laisser le conteneur app finir son initialisation
    sleep 5
fi

# ── 6. Démarrage du processus principal ──────────────────────────
# Par défaut : PHP-FPM. Si une commande est passée en argument, elle est exécutée à la place.
# Ex : docker compose worker → exec php bin/console messenger:consume scheduler_default
if [ "$#" -gt 0 ]; then
    echo ""
    echo "🚀  Démarrage : $*"
    echo ""
    exec "$@"
else
    echo ""
    echo "🚀  Démarrage PHP-FPM..."
    echo ""
    exec php-fpm
fi
