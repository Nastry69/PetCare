# 🐾 PetCare

Application web de gestion du suivi animalier — projet fil rouge CDA IPSSI (2026).

> **PetCare** permet aux propriétaires d'animaux de centraliser soins, vaccins et rendez-vous vétérinaires, avec rappels automatiques et collaboration multi-utilisateurs.

---

## Stack technique

| Couche | Technologie |
|---|---|
| Backend | PHP 8.4 / Symfony 7.4 (API REST) |
| Frontend | React 19 + Vite + Tailwind CSS (SPA) |
| Base de données | PostgreSQL 16 |
| ORM | Doctrine |
| Authentification | JWT (LexikJWT) + OAuth2 Google |
| Emails | Symfony Mailer + Brevo SMTP (prod) / Mailpit (dev) |
| Rappels planifiés | Symfony Scheduler + Messenger (cron 8h) |
| Conteneurisation | Docker + Docker Compose |
| CI | GitHub Actions (PHPUnit + Vitest + build) |

---

## Prérequis

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) ≥ 4.x
- [Docker Compose](https://docs.docker.com/compose/) ≥ 2.x (inclus dans Docker Desktop)
- Git

> PHP, Node.js et Composer **ne sont pas nécessaires** en local — tout tourne dans Docker.

---

## Installation rapide (Docker)

### 1. Cloner le projet

```bash
git clone https://github.com/Nastry69/PetCare.git
cd PetCare
```

### 2. Configurer les variables d'environnement *(optionnel pour tester)*

Sans `.env`, des valeurs par défaut sont utilisées et les clés JWT sont générées automatiquement. Pour personnaliser :

```bash
cp .env.example .env
# Éditez .env et renseignez APP_SECRET, POSTGRES_PASSWORD, JWT_PASSPHRASE, MAILER_DSN…
```

| Variable | Description |
|---|---|
| `APP_SECRET` | Chaîne aléatoire ≥ 32 caractères |
| `POSTGRES_PASSWORD` | Mot de passe PostgreSQL |
| `JWT_PASSPHRASE` | Passphrase pour les clés RSA JWT |
| `MAILER_DSN` | DSN SMTP (Mailpit dev ou Brevo prod) |
| `FRONTEND_URL` | URL du frontend pour les liens dans les emails |

Générer des valeurs sécurisées :
```bash
openssl rand -hex 32   # Pour APP_SECRET et JWT_PASSPHRASE
```

### 3. Démarrer la stack complète

```bash
docker compose up -d --build
```

> ⏱️ Le premier démarrage prend ~3-5 minutes (build des images React + PHP).

**C'est tout.** L'entrypoint gère automatiquement :
- ✅ Génération des clés JWT RSA 4096 bits (si absentes)
- ✅ Création du schéma PostgreSQL + migrations Doctrine
- ✅ Insertion des types d'événements par défaut (Vaccin, Consultation…)
- ✅ Warmup du cache Symfony

---

## Accès

| Service | URL | Description |
|---|---|---|
| **Application** (React) | http://localhost:3000 | Interface utilisateur |
| **API Backend** (Symfony) | http://localhost:8000/api | API REST JSON |
| **Mailpit** (emails dev) | http://localhost:8025 | Boîte mail de développement |
| **PostgreSQL** | localhost:5432 | Base de données |

---

## Services Docker

| Service | Rôle |
|---|---|
| `app` | PHP 8.4-FPM — API Symfony |
| `nginx` | Reverse proxy → app (:8000) |
| `frontend` | SPA React build nginx (:3000) |
| `database` | PostgreSQL 16 |
| `worker` | Symfony Messenger consumer — rappels email planifiés à 8h |
| `mailer` | Mailpit — SMTP dev (:1025 / :8025) |

---

## Commandes utiles

```bash
# Voir les logs en temps réel
docker compose logs -f

# Logs d'un service spécifique
docker compose logs -f app
docker compose logs -f worker

# Vider le cache Symfony
docker compose exec app php bin/console cache:clear

# Envoyer les rappels email manuellement
docker compose exec app php bin/console app:send-reminders

# Lancer les tests PHPUnit (backend)
docker compose exec app vendor/bin/phpunit

# Ouvrir un shell dans le conteneur PHP
docker compose exec app bash

# Arrêter la stack
docker compose down

# Arrêter et supprimer les données (⚠️ supprime la BDD)
docker compose down -v
```

---

## Fonctionnalités

| # | Fonctionnalité | Description |
|---|---|---|
| F1 | Gestion des utilisateurs | Inscription, connexion JWT, OAuth Google, profil éditable |
| F2 | Gestion des animaux | CRUD complet, photo, fiche détaillée |
| F3 | Suivi des événements et soins | Vaccins, visites, traitements — historique complet |
| F4 | Rappels automatiques | Email J-N avant événement via Symfony Scheduler (8h chaque matin) |
| F5 | Partage de fiches | Invitation par email, rôles lecture / écriture |
| F6 | Conformité RGPD | Export des données personnelles, suppression de compte |

---

## Endpoints API

| Méthode | Route | Auth | Description |
|---|---|---|---|
| POST | `/api/register` | Non | Inscription |
| POST | `/api/auth/login_check` | Non | Connexion JWT |
| GET | `/api/auth/google` | Non | Redirect OAuth2 Google |
| POST | `/api/auth/forgot-password` | Non | Demande réinitialisation MDP |
| POST | `/api/auth/reset-password` | Non | Réinitialisation MDP |
| GET | `/api/me` | JWT | Profil courant |
| PUT | `/api/me` | JWT | Modifier profil |
| DELETE | `/api/me` | JWT | Supprimer compte (RGPD) |
| GET | `/api/me/export` | JWT | Export données personnelles |
| POST | `/api/me/photo` | JWT | Upload photo de profil |
| GET | `/api/animals` | JWT | Lister mes animaux |
| POST | `/api/animals` | JWT | Ajouter un animal |
| GET | `/api/animals/{id}` | JWT | Détail d'un animal |
| PUT | `/api/animals/{id}` | JWT | Modifier un animal |
| DELETE | `/api/animals/{id}` | JWT | Supprimer un animal |
| POST | `/api/animals/{id}/photo` | JWT | Upload photo animal |
| GET | `/api/evenements` | JWT | Lister les événements |
| GET | `/api/evenements/upcoming` | JWT | Prochains événements |
| POST | `/api/evenements` | JWT | Créer un événement |
| GET | `/api/evenements/{id}` | JWT | Détail événement |
| PUT | `/api/evenements/{id}` | JWT | Modifier un événement |
| DELETE | `/api/evenements/{id}` | JWT | Supprimer un événement |
| GET | `/api/type-evenements` | JWT | Types d'événements |
| GET | `/api/partages` | JWT | Partages reçus |
| POST | `/api/partages` | JWT | Partager un animal |
| GET | `/api/partages/animal/{id}` | JWT | Partages d'un animal |
| PUT | `/api/partages/{id}` | JWT | Modifier rôle partage |
| DELETE | `/api/partages/{id}` | JWT | Révoquer un partage |

---

## Structure du projet

```
PetCare/
├── backend/                  # Symfony 7.4 — API REST
│   ├── src/
│   │   ├── Controller/       # Routes HTTP
│   │   ├── Entity/           # Entités Doctrine
│   │   ├── Repository/       # Requêtes Doctrine ORM
│   │   ├── Service/          # Logique métier
│   │   ├── Command/          # Console commands (app:send-reminders)
│   │   ├── Message/          # Messages Messenger
│   │   ├── MessageHandler/   # Handlers rappels email
│   │   └── Schedule.php      # Symfony Scheduler (cron 8h)
│   ├── migrations/           # Migrations Doctrine
│   ├── tests/
│   │   ├── Unit/             # Tests unitaires PHPUnit
│   │   └── Functional/       # Tests fonctionnels (SQLite)
│   ├── config/               # Configuration Symfony
│   └── docker/               # Dockerfile PHP + config nginx
│
├── frontend/                 # React 19 + Vite + Tailwind CSS
│   ├── src/
│   │   ├── api/              # Client Axios
│   │   ├── context/          # AuthContext (JWT)
│   │   ├── components/       # Layout, Sidebar, Topbar
│   │   └── pages/            # Dashboard, Animals, Calendar…
│   ├── Dockerfile            # Build production → nginx
│   └── nginx.conf            # Config nginx SPA
│
├── docker-compose.yml        # Stack complète (6 services)
├── .env.example              # Template variables d'environnement
├── README.md                 # Ce fichier
├── Docs/                     # Documentation jalons IPSSI
└── .github/workflows/ci.yml  # CI GitHub Actions
```

---

## Tests

### Backend (PHPUnit)

```bash
# Via Docker
docker compose exec app vendor/bin/phpunit

# Unitaires uniquement
docker compose exec app vendor/bin/phpunit tests/Unit

# Fonctionnels uniquement
docker compose exec app vendor/bin/phpunit tests/Functional
```

### Frontend (Vitest)

```bash
cd frontend && npm test
```

### CI GitHub Actions

Les tests s'exécutent automatiquement à chaque push sur `main` et `develop`.

---

## Configuration emails (production)

Par défaut, les emails sont capturés par **Mailpit** (http://localhost:8025) en développement.

Pour utiliser **Brevo** (ou tout autre SMTP) en production, renseignez dans `.env` :

```env
MAILER_DSN=smtp://user%40smtp-brevo.com:CLE_SMTP@smtp-relay.brevo.com:587
MAILER_FROM_EMAIL=votre@email.fr
MAILER_FROM_NAME=PetCare
FRONTEND_URL=https://votre-domaine.fr
```

Emails envoyés automatiquement :
- **Bienvenue** à l'inscription (email + Google OAuth)
- **Rappel d'événement** J-N avant la date (si `rappelActif = true`)
- **Réinitialisation de mot de passe**
- **Invitation** lors d'un partage d'animal

---

## Configuration OAuth Google (optionnel)

1. Créez un projet sur [Google Cloud Console](https://console.cloud.google.com)
2. Activez **Google Identity API**
3. Créez un **OAuth 2.0 Client ID** (Application Web)
4. URI de redirection autorisée : `http://localhost:8000/api/auth/google/callback`
5. Renseignez `GOOGLE_CLIENT_ID` et `GOOGLE_CLIENT_SECRET` dans `.env`

---

## Gestion des branches

| Branche | Rôle |
|---|---|
| `main` | Versions stables — livraisons jalons |
| `develop` | Développement en cours |

---

## Auteur

**Tristan DZIOCH** — Formation CDA IPSSI (2025-2026)  
GitHub : [Nastry69/PetCare](https://github.com/Nastry69/PetCare)
