# PetCare

Application web de gestion et de suivi de la santé des animaux domestiques.

Projet fil rouge — Formation Concepteur Développeur d'Applications (CDA Bac+3) — 2026

---

## Présentation

PetCare permet aux propriétaires d'animaux de centraliser toutes les informations de santé de leurs compagnons : vaccins, rendez-vous vétérinaires, traitements, rappels automatiques et partage d'accès avec l'entourage. **Plus besoin de carnet de santé papier.**

---

## Stack technique

| Couche | Technologie |
|---|---|
| Backend | PHP 8.4 · Symfony 7.4 · API REST |
| Authentification | JWT — LexikJWTAuthenticationBundle |
| Frontend | React 19 · Vite · Tailwind CSS |
| Base de données | MySQL / MariaDB · Doctrine ORM |
| Versioning | Git · GitHub |

---

## Fonctionnalités (Jalon 5)

- **Authentification** — inscription, connexion JWT, gestion du profil
- **Animaux** — CRUD complet avec fiche détaillée par animal
- **Événements vétérinaires** — création, suivi par statut, calendrier mensuel
- **Rappels automatiques** — notification J-N avant un rendez-vous
- **Partage** — invitation par email, accès consultation ou gestion
- **Notifications** — système en temps réel (partages, rappels)
- **RGPD** — export JSON des données, suppression de compte
- **Landing page** — page de présentation publique

---

## Structure du projet

```
petcare/
├── backend/          # API Symfony
├── frontend/         # SPA React
├── docs/
│   ├── 1_Cahier des Charges Fonctionnel.pdf
│   ├── 2_Methodologie et Conception UI & UX.pdf
│   └── 3_Modélisation de la Base de Données.pdf
└── README.md
```

---

## Installation

### Prérequis

- PHP 8.4+ avec extensions `sodium`, `pdo_mysql`, `openssl`
- Composer
- Node.js 20+
- MySQL / MariaDB

### Backend

```bash
cd backend
composer install
# Configurer backend/.env (copier depuis .env.example)
php bin/console lexik:jwt:generate-keypair
php bin/console doctrine:migrations:migrate
php -S localhost:8000 -t public
```

### Frontend

```bash
cd frontend
npm install
npm run dev
```

L'application est accessible sur `http://localhost:5173`

---

## Rappels automatiques

La commande suivante envoie les notifications de rappel (à planifier via cron) :

```bash
php bin/console app:send-reminders
```

Exemple cron — tous les jours à 8h :
```
0 8 * * * php /chemin/vers/backend/bin/console app:send-reminders
```

---

## Gestion des branches

| Branche | Rôle |
|---|---|
| `main` | Versions stables et livrables validés |
| `develop` | Intégration des fonctionnalités en cours |

---

## Auteur

Projet réalisé par Tristan dans le cadre de la formation CDA — IPSSI 2026.
