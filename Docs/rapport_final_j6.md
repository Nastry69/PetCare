# PetCare — Rapport Final
## Jalon 6 — Livrable Final (Juin 2026)

**Formation :** Concepteur Développeur d'Applications (CDA Bac+3) — IPSSI  
**Auteur :** Tristan DZIOCH  
**Année :** 2025 – 2026  
**GitHub :** https://github.com/Nastry69/PetCare  
**Version :** v1.0

---

## Sommaire

- [Chapitre III — Cahier des Charges Fonctionnel](#chapitre-iii--cahier-des-charges-fonctionnel)
- [Chapitre IV — Méthodologie de Projet](#chapitre-iv--méthodologie-de-projet)
- [Chapitre V — Conception UI/UX](#chapitre-v--conception-uiux)
- [Chapitre VI — Modélisation Base de Données](#chapitre-vi--modélisation-base-de-données)
- [Chapitre VII — Conception UML](#chapitre-vii--conception-uml)
- [Chapitre VIII — Architecture de l'Application](#chapitre-viii--architecture-de-lapplication)
- [Chapitre IX — Sécurité](#chapitre-ix--sécurité)
- [Chapitre X — Tests](#chapitre-x--tests)
- [Chapitre XI — Déploiement](#chapitre-xi--déploiement)

---

# Chapitre III — Cahier des Charges Fonctionnel

## III.1 Contexte et Problématique

Les propriétaires d'animaux de compagnie peinent à centraliser le suivi médical de leurs animaux. Les informations sont dispersées : carnets de santé papier, rappels manuels, partage d'informations difficile avec le vétérinaire ou un proche.

**PetCare** répond à ce besoin en proposant une application web qui centralise l'ensemble du suivi animalier, avec rappels automatiques et collaboration entre utilisateurs.

## III.2 Objectifs SMART

| Objectif | Critère | Mesure |
|---|---|---|
| Centraliser le suivi de santé | Spécifique | Vaccins, visites, traitements accessibles sur un seul écran |
| Rappels automatiques | Mesurable | 100 % des rappels activés reçoivent un email J-N |
| Collaboration | Atteignable | Partage en lecture ou écriture entre utilisateurs |
| Conformité RGPD | Réaliste | Export + suppression des données en < 5 secondes |
| Livraison en 6 mois | Temporel | Jalons mensuels de janvier à juin 2026 |

## III.3 Fonctionnalités V1

### F1 — Gestion des utilisateurs
- Inscription classique (email + mot de passe hashé Argon2id)
- Connexion classique et via OAuth2 Google
- Consultation et modification du profil (nom, prénom, email, photo)
- Export des données personnelles (RGPD)
- Suppression de compte avec cascade des données associées

### F2 — Gestion des animaux
- Ajout, modification, suppression d'un animal
- Fiche animal : nom, espèce, race, date de naissance, sexe, photo
- Liste de tous les animaux (propres + partagés)

### F3 — Suivi des événements et soins
- Création d'événements liés à un animal
- Types d'événements : Vaccin, RDV Vétérinaire, Traitement, Toilettage, Autre
- Statuts : planifié, prévu, à confirmer, effectué, annulé
- Commentaire libre sur chaque événement
- Historique complet avec filtre et calendrier

### F4 — Rappels automatiques
- Activation d'un rappel par événement
- Configuration du délai en jours (J-1 à J-30)
- Envoi automatique par email via CRON tous les matins à 8h

### F5 — Partage et collaboration
- Invitation d'un autre utilisateur via son email
- Rôles différenciés : `lecture` / `écriture`
- L'invité voit les animaux partagés dans son espace

### F6 — Conformité RGPD
- Export de toutes les données personnelles (format JSON / CSV / HTML)
- Suppression du compte avec effacement de toutes les données (cascade BDD)

## III.4 Exigences techniques

| Exigence | Valeur |
|---|---|
| Temps de réponse API | < 1 seconde pour les requêtes courantes |
| Disponibilité | Application accessible 24h/24 via Docker |
| Responsive | Desktop + mobile (Tailwind CSS) |
| Sécurité | OWASP Top 10, JWT, Argon2id, RGPD |
| Norme code | PSR-12, MVC strict, DRY/KISS |

## III.5 Risques identifiés

| Risque | Probabilité | Impact | Mitigation |
|---|---|---|---|
| Dépendance Google OAuth | Faible | Élevé | Authentification classique en fallback |
| Dépendance SendGrid/SMTP | Faible | Moyen | Service email configurable via `.env` |
| Charge de développement (projet individuel) | Moyenne | Moyen | Priorisation stricte V1, jalons mensuels, features V2 reportées |

---

# Chapitre IV — Méthodologie de Projet

## IV.1 Méthode de gestion de projet

Le projet PetCare est développé en suivant une **approche agile adaptée** au contexte individuel (pas d'équipe). La méthode retenue s'inspire de **Scrum** avec des sprints d'un mois correspondant aux jalons IPSSI.

### Principes appliqués
- **Itérations mensuelles** (Jalons J1 à J6) avec livrable à chaque fin de sprint
- **Backlog priorisé** : fonctionnalités V1 en premier, V2 reportées
- **CI/CD** : tests automatiques à chaque commit, merge via Pull Requests

## IV.2 Stratégie de branches (GitFlow)

| Branche | Rôle |
|---|---|
| `main` | Versions stables — taggées à chaque jalon |
| `develop` | Branche d'intégration principale |
| `feature/xxx` | Développement d'une fonctionnalité |
| `fix/xxx` | Corrections de bugs |

**Règles :**
- Aucun commit direct sur `main`
- Chaque fonctionnalité passe par une PR vers `develop`
- `develop` est mergé dans `main` à chaque livraison de jalon

## IV.3 Planning des jalons

| Jalon | Mois | Échéance | Livrable |
|---|---|---|---|
| J1 | Janvier 2026 | 31/01 | Cahier des Charges Fonctionnel |
| J2 | Février 2026 | 28/02 | Méthodologie + UI/UX |
| J3 | Mars 2026 | 31/03 | Modélisation BDD (MERISE + SQL) |
| J4 | Avril 2026 | 30/04 | Architecture + UML |
| J5 | Mai 2026 | 29/05 | Version bêta + tests + CI |
| J6 | Juin 2026 | 30/06 | Livrable final v1.0 + Docker + rapport |

## IV.4 Pipeline CI/CD (GitHub Actions)

Le fichier `.github/workflows/ci.yml` définit trois jobs exécutés à chaque push sur `main` et `develop` :

1. **`backend-tests`** — PHPUnit tests unitaires + fonctionnels (PHP 8.4, SQLite en mémoire)
2. **`backend-lint`** — Vérification syntaxe PHP (`php -l`)
3. **`frontend-build`** — Vitest + build production (Node 20 & 22)

---

# Chapitre V — Conception UI/UX

## V.1 Arborescence de l'application

```
PetCare
├── / (Landing page publique)
├── /login        — Connexion
├── /register     — Inscription
├── /forgot-password — Réinitialisation mot de passe
├── /auth/callback   — Retour OAuth Google
└── [Espace privé — JWT requis]
    ├── /dashboard     — Tableau de bord (stats, graphiques, prochains RDV)
    ├── /animals       — Liste des animaux
    ├── /animals/:id   — Fiche détaillée d'un animal + événements
    ├── /events/new    — Créer un événement
    ├── /events/:id/edit — Modifier un événement
    ├── /calendar      — Calendrier mensuel des événements
    └── /settings      — Profil, mot de passe, RGPD export/suppression
```

## V.2 Charte graphique

| Élément | Valeur |
|---|---|
| Couleur primaire | `#1377EC` (bleu PetCare) |
| Couleur succès | `#22C55E` (vert) |
| Couleur avertissement | `#F59E0B` (orange) |
| Couleur erreur/danger | `#EF4444` (rouge) |
| Texte principal | `#0F172A` (quasi-noir) |
| Texte secondaire | `#64748B` (gris) |
| Fond général | `#F8FAFC` (blanc cassé) |
| Police | Inter (system-ui fallback) |
| Radius | 16px (cards), 12px (boutons), 8px (badges) |

## V.3 Composants principaux

- **Layout** : Sidebar gauche (navigation) + Topbar (user menu)
- **Dashboard** : 4 cards statistiques + donut chart (répartition RDV) + tableau des prochains événements
- **Fiche animal** : photo, informations, liste des événements avec actions rapides
- **EventForm** : formulaire unifié création/modification avec sélecteur de type, date-time, rappel configurable
- **Calendar** : grille mensuelle des événements avec navigation
- **Settings** : édition profil, upload photo, export RGPD (JSON/CSV/HTML), suppression compte

## V.4 Responsive

L'interface est responsive grâce à Tailwind CSS. La sidebar est collapsible sur mobile. Les grilles passent de 4 colonnes (desktop) à 1 colonne (mobile).

---

# Chapitre VI — Modélisation Base de Données

## VI.1 Démarche MERISE

La modélisation suit la méthode **MERISE** en 3 niveaux : MCD → MLD → MPD.

## VI.2 Dictionnaire des données

### Entité : utilisateur

| Attribut | Type SQL | Contrainte | Description |
|---|---|---|---|
| id_utilisateur | INT | PK, AUTO_INCREMENT | Identifiant unique |
| nom | VARCHAR(100) | NOT NULL | Nom de famille |
| prenom | VARCHAR(100) | NOT NULL | Prénom |
| email | VARCHAR(255) | NOT NULL, UNIQUE | Adresse email |
| mot_de_passe_hash | VARCHAR(255) | NOT NULL | Hash Argon2id |
| date_inscription | DATETIME | NOT NULL, DEFAULT NOW() | Date de création |
| role | JSON | NOT NULL | Rôles applicatifs (ROLE_USER) |
| photo_url | VARCHAR(500) | NULL | URL photo de profil |

### Entité : animal

| Attribut | Type SQL | Contrainte | Description |
|---|---|---|---|
| id_animal | INT | PK, AUTO_INCREMENT | Identifiant unique |
| nom | VARCHAR(100) | NOT NULL | Nom de l'animal |
| espece | VARCHAR(100) | NOT NULL | Espèce (chien, chat…) |
| race | VARCHAR(100) | NULL | Race (optionnelle) |
| date_naissance | DATE | NULL | Date de naissance |
| sexe | VARCHAR(10) | NULL | M / F |
| photo_url | VARCHAR(500) | NULL | URL photo |
| id_utilisateur | INT | NOT NULL, FK | Propriétaire |

### Entité : type_evenement

| Attribut | Type SQL | Contrainte |
|---|---|---|
| id_type_evenement | INT | PK, AUTO_INCREMENT |
| libelle | VARCHAR(100) | NOT NULL |
| couleur | VARCHAR(7) | NOT NULL (code hex) |

### Entité : evenement

| Attribut | Type SQL | Contrainte | Description |
|---|---|---|---|
| id_evenement | INT | PK, AUTO_INCREMENT | Identifiant |
| id_animal | INT | NOT NULL, FK | Animal concerné |
| id_type_evenement | INT | NOT NULL, FK | Type d'événement |
| id_createur | INT | NOT NULL, FK | Créateur |
| date_heure_evenement | DATETIME | NOT NULL | Date et heure |
| statut | VARCHAR(50) | DEFAULT 'prevu' | État |
| rappel_actif | BOOLEAN | DEFAULT false | Rappel activé |
| rappel_jours_avant | INT | NULL | Délai rappel |
| commentaire | VARCHAR(500) | NULL | Note libre |
| date_creation | DATETIME | NOT NULL, DEFAULT NOW() | |
| date_modification | DATETIME | NULL | |

### Entité : partage_animal (table de jonction)

| Attribut | Type SQL | Contrainte |
|---|---|---|
| id_animal | INT | PK, FK → animal |
| id_utilisateur | INT | PK, FK → utilisateur |
| role_partage | VARCHAR(50) | NOT NULL ('lecture' / 'ecriture') |
| date_invitation | DATETIME | NOT NULL, DEFAULT NOW() |

## VI.3 Règles de gestion

| N° | Règle |
|---|---|
| RG01 | Un utilisateur peut posséder zéro ou plusieurs animaux. Un animal appartient à un seul utilisateur. |
| RG02 | Un animal peut avoir zéro ou plusieurs événements. Un événement concerne un seul animal. |
| RG03 | Un événement est créé par un seul utilisateur. |
| RG04 | Un événement a un seul type d'événement. |
| RG05 | Un animal peut être partagé avec zéro ou plusieurs utilisateurs (rôle associé). |
| RG06 | La suppression d'un utilisateur supprime en cascade ses animaux et ses événements. |
| RG07 | La suppression d'un animal supprime en cascade ses événements et partages. |

## VI.4 MCD — Associations

```
utilisateur (0,n) ─── possède ─── (1,1) animal
utilisateur (0,n) ─── crée ─── (1,1) evenement
utilisateur (0,n) ─── partage_animal ─── (0,n) animal
animal (0,n) ─── génère ─── (1,1) evenement
type_evenement (0,n) ─── qualifie ─── (1,1) evenement
```

## VI.5 MLD — Modèle Logique

```
utilisateur (#id_utilisateur, nom, prenom, email, mot_de_passe_hash, date_inscription, role, photo_url)

animal (#id_animal, nom, espece, race, date_naissance, sexe, photo_url, id_utilisateur→utilisateur)

type_evenement (#id_type_evenement, libelle, couleur)

evenement (#id_evenement, id_animal→animal, id_type_evenement→type_evenement,
           id_createur→utilisateur, date_heure_evenement, statut, rappel_actif,
           rappel_jours_avant, commentaire, date_creation, date_modification)

partage_animal (#id_animal→animal, #id_utilisateur→utilisateur, role_partage, date_invitation)
```

---

# Chapitre VII — Conception UML

## VII.1 Diagramme de Cas d'Utilisation

### Acteurs

| Acteur | Description |
|---|---|
| **Visiteur** | Utilisateur non authentifié — accès inscription/connexion uniquement |
| **Utilisateur authentifié** | Propriétaire — accès complet à toutes les fonctionnalités |
| **Utilisateur invité** | Accès limité aux animaux partagés selon son rôle |

### Cas d'utilisation par fonctionnalité

| Fonctionnalité | Cas d'utilisation |
|---|---|
| F1 – Utilisateurs | S'inscrire, Se connecter, Modifier profil, Supprimer compte, Exporter données |
| F2 – Animaux | Ajouter/Modifier/Supprimer/Lister animaux, Upload photo |
| F3 – Événements | Créer/Modifier/Supprimer/Consulter événements, Filtrer par type/statut |
| F4 – Rappels | Activer rappel, Configurer délai, Recevoir email |
| F5 – Partage | Inviter utilisateur, Définir rôle, Révoquer partage, Consulter animaux partagés |
| F6 – RGPD | Exporter données, Supprimer compte |

## VII.2 Diagramme de Séquence 1 — Connexion JWT

```
Utilisateur → React SPA → POST /api/auth/login_check { email, password }
React SPA → SecurityController (Symfony)
SecurityController → UserRepository → findByEmail(email)
UserRepository → BDD → SELECT utilisateur
BDD → UserRepository → User entity
SecurityController → PasswordHasher → isPasswordValid(user, password)
PasswordHasher → SecurityController → true/false
SecurityController → LexikJWTBundle → create(user)
LexikJWTBundle → SecurityController → JWT signé (RSA 2048)
SecurityController → React SPA → 200 { token }
React SPA → localStorage → setItem('token', jwt)
React SPA → Axios intercepteur → Authorization: Bearer {token} sur chaque requête
```

## VII.3 Diagramme de Séquence 2 — Création d'un événement avec rappel

```
Utilisateur → React SPA → Formulaire (animal, type, date, rappel_actif=true, rappel_jours_avant=3)
React SPA → POST /api/evenements (JWT header)
EvenementController → Symfony Security → vérification JWT
EvenementController → EvenementService → create(data, user)
EvenementService → AnimalRepository → isAccessibleByUser(animal, user)
AnimalRepository → EvenementService → true
EvenementService → EntityManager → persist(Evenement)
EntityManager → PostgreSQL → INSERT INTO evenement
EvenementController → React SPA → 201 Created { evenement }

--- Tâche CRON (chaque jour à 8h) ---
Symfony Scheduler → DailyReminderMessageHandler → handle()
DailyReminderMessageHandler → EvenementRepository → findRappelsDuJour()
EvenementRepository → DailyReminderMessageHandler → [evenements à rappeler]
DailyReminderMessageHandler → MailerService → sendReminderEmail(evenement)
MailerService → SMTP (Mailpit/SendGrid) → Email envoyé à l'utilisateur
```

## VII.4 Diagramme de Séquence 3 — Partage d'un animal

```
Propriétaire → React SPA → POST /api/partages { animal_id, email, rolePartage }
React SPA → PartageAnimalController (JWT header)
PartageAnimalController → AnimalRepository → find(animal_id)
PartageAnimalController → Vérification propriété → animal.proprietaire === user ?
PartageAnimalController → UserRepository → findByEmail(email invité)
PartageAnimalController → PartageAnimalService → create({ animal_id, utilisateur_id, rolePartage })
PartageAnimalService → EntityManager → persist(PartageAnimal)
EntityManager → PostgreSQL → INSERT INTO partage_animal
PartageAnimalService → MailerService → sendInvitationEmail(invité, propriétaire, animal, rôle)
MailerService → SMTP → Email d'invitation envoyé
PartageAnimalController → React SPA → 201 Created { partage }
```

## VII.5 Diagramme de Classes (simplifié)

```
User
  - id: int
  - nom: string
  - prenom: string
  - email: string (unique)
  - password: string (Argon2id hash)
  - dateInscription: DateTimeImmutable
  - roles: array
  - photoUrl: ?string
  - animals: Collection<Animal>
  - evenements: Collection<Evenement>
  + getRoles(): array
  + getUserIdentifier(): string

Animal
  - id: int
  - nom: string
  - espece: string
  - race: ?string
  - dateNaissance: ?Date
  - sexe: ?string
  - photoUrl: ?string
  - proprietaire: User
  - evenements: Collection<Evenement>
  - partageAnimals: Collection<PartageAnimal>

TypeEvenement
  - id: int
  - libelle: string
  - couleur: string

Evenement
  - id: int
  - animal: Animal
  - typeEvenement: TypeEvenement
  - createur: User
  - dateHeureEvenement: DateTime
  - statut: string
  - rappelActif: bool
  - rappelJoursAvant: ?int
  - commentaire: ?string
  - dateCreation: DateTime
  - dateModification: ?DateTime

PartageAnimal
  - animal: Animal
  - utilisateur: User
  - rolePartage: string ('lecture' | 'ecriture')
  - dateInvitation: DateTime
```

---

# Chapitre VIII — Architecture de l'Application

## VIII.1 Architecture n-Tiers

L'application suit une architecture **3 tiers** conteneurisée via Docker Compose :

| Tier | Technologie | Rôle |
|---|---|---|
| Présentation | React 19 + Nginx | SPA servi statiquement, responsive |
| Application | Symfony 7.4 + PHP-FPM + Nginx | API REST JSON, logique métier, JWT, CRON |
| Données | PostgreSQL 16 | Persistance, accès exclusif via Doctrine ORM |

**Services externes :** Google OAuth2 API (authentification), SMTP (emails rappels/bienvenue). Clés en variables d'environnement.

## VIII.2 Pattern MVC (Backend)

```
src/
├── Controller/     # Réception HTTP, validation entrée, réponse JSON
│   ├── SecurityController.php     → /api/register, /api/auth/*, /api/me
│   ├── AnimalController.php       → /api/animals/*
│   ├── EvenementController.php    → /api/evenements/*
│   ├── PartageAnimalController.php→ /api/partages/*
│   ├── TypeEvenementController.php→ /api/type-evenements
│   └── GoogleAuthController.php   → /api/auth/google/*
├── Service/        # Logique métier, orchestration
│   ├── AnimalService.php
│   ├── EvenementService.php
│   ├── PartageAnimalService.php
│   └── MailerService.php
├── Repository/     # Requêtes Doctrine ORM
│   ├── UserRepository.php
│   ├── AnimalRepository.php
│   ├── EvenementRepository.php
│   └── PartageAnimalRepository.php
├── Entity/         # Entités Doctrine (mapping ORM)
│   ├── User.php
│   ├── Animal.php
│   ├── Evenement.php
│   ├── TypeEvenement.php
│   └── PartageAnimal.php
├── Message/        # DailyReminderMessage.php
├── MessageHandler/ # DailyReminderMessageHandler.php
└── Schedule.php    # CRON — 0 8 * * * (rappels quotidiens)
```

## VIII.3 Infrastructure Docker Compose

```yaml
services:
  app:       # PHP 8.4-FPM + Symfony 7.4
  nginx:     # Reverse proxy API (port 8000)
  frontend:  # React build de prod (nginx, port 3000)
  database:  # PostgreSQL 16
  mailer:    # Mailpit SMTP dev (port 8025)
```

## VIII.4 Flux de données

```
Navigateur (React)
  │
  ├── GET/POST/PUT/DELETE http://localhost:8000/api/...
  │     → nginx (port 8000) → PHP-FPM (port 9000) → Symfony
  │           → Doctrine ORM → PostgreSQL
  │
  └── CRON (Symfony Scheduler, 0 8 * * *)
        → DailyReminderMessageHandler
              → MailerService → SMTP → Email utilisateur
```

## VIII.5 Sécurité des flux

- **JWT** : token inclus dans chaque requête (`Authorization: Bearer`)
- **CORS** : `nelmio/cors-bundle` — origines autorisées configurées via `.env`
- **Accès BDD** : exclusivement depuis le conteneur `app` (réseau Docker interne)

---

# Chapitre IX — Sécurité

## IX.1 OWASP Top 10 — État de l'implémentation

### A01 — Broken Access Control ✅ Couvert

Chaque endpoint vérifie l'identité et la propriété :

```php
// AnimalController.php — vérification propriété avant toute opération
if (!$animalRepository->isAccessibleByUser($animal, $user)) {
    return $this->json(['message' => 'Accès refusé.'], 403);
}

// PartageAnimalController.php — seul le propriétaire peut partager
if ($animal->getProprietaire() !== $user) {
    return $this->json(['message' => 'Accès refusé.'], 403);
}
```

Toutes les routes `/api` (sauf `/register`, `/auth/login_check`, `/auth/google`) requièrent `IS_AUTHENTICATED_FULLY` dans `security.yaml`.

### A02 — Cryptographic Failures ✅ Couvert

- **Mots de passe** : hashés avec `auto` (Argon2id sur PHP 8+), jamais stockés en clair
- **JWT** : clés RSA 2048 bits générées par `lexik:jwt:generate-keypair`, passphrase en variable d'environnement
- **Secrets** : `APP_SECRET`, `JWT_PASSPHRASE`, `POSTGRES_PASSWORD` en variables d'environnement, jamais committés

### A03 — Injection ✅ Couvert

**Doctrine ORM** est utilisé pour toutes les requêtes BDD. Les paramètres sont automatiquement préparés et échappés — aucune requête SQL brute sans binding.

```php
// Exemple : AnimalRepository — requête préparée via DQL
return $this->createQueryBuilder('a')
    ->where('a.proprietaire = :user')
    ->setParameter('user', $user)
    ->getQuery()->getResult();
```

### A04 — Insecure Design ✅ Couvert (partiellement)

- Architecture stateless JWT — pas de session serveur à pirater
- Séparation des responsabilités : Controller → Service → Repository
- Validation des données entrantes dans chaque Controller

### A05 — Security Misconfiguration ✅ Couvert

- Variables d'environnement pour tous les secrets
- CORS configuré et restrictif (`nelmio/cors-bundle`)
- Headers de sécurité HTTP dans la config nginx frontend (`X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`)
- Accès PostgreSQL limité au réseau Docker interne

### A06 — Vulnerable and Outdated Components ✅ Couvert

Stack récente et maintenue :
- PHP 8.4 (décembre 2024)
- Symfony 7.4 (LTS, novembre 2024)
- React 19 (décembre 2024)
- PostgreSQL 16

### A07 — Identification and Authentication Failures ✅ Couvert

- JWT stateless, signé RSA 2048 bits
- Pas de session côté serveur
- Validation email et longueur mot de passe (≥ 8 caractères) à l'inscription
- OAuth2 Google via `knpuniversity/oauth2-client-bundle`

### A08 — Software and Data Integrity Failures ✅ Couvert

- `composer.lock` et `package-lock.json` verrouillent les versions des dépendances
- Pipeline CI exécute les tests à chaque push — aucune régression non détectée

### A09 — Security Logging and Monitoring Failures 🟡 Partiel

- Symfony Log de base activé (erreurs HTTP 4xx et 5xx)
- Amélioration possible en V2 : journalisation des tentatives de connexion échouées

### A10 — Server-Side Request Forgery ✅ Non applicable

L'application ne fait pas de requêtes HTTP vers des URL fournies par l'utilisateur.

## IX.2 CSRF

Pour une API REST stateless (JWT dans `Authorization: Bearer`), le CSRF n'est **pas applicable** : il ne peut se produire que si l'authentification passe par un cookie de session. Les navigateurs ne peuvent pas envoyer automatiquement un header `Authorization` depuis un site tiers.

## IX.3 XSS

- **Backend** : `htmlspecialchars()` sur toutes les données insérées dans les templates emails
- **Frontend** : React échappe automatiquement les données injectées dans le JSX via `{}` — aucun `dangerouslySetInnerHTML` utilisé

## IX.4 RGPD

- **Export des données** : endpoint `GET /api/me/export` retourne toutes les données personnelles (user + animaux + événements) — formats JSON, CSV et HTML disponibles côté frontend
- **Droit à l'effacement** : endpoint `DELETE /api/me` supprime l'utilisateur avec CASCADE en BDD (animaux → événements → partages)

---

# Chapitre X — Tests

## X.1 Stratégie de tests

| Niveau | Outil | Portée |
|---|---|---|
| Tests unitaires backend | PHPUnit 11 | Services, Entités — sans BDD |
| Tests fonctionnels backend | PHPUnit 11 + Symfony WebTestCase | Endpoints HTTP — SQLite en mémoire |
| Tests unitaires frontend | Vitest + React Testing Library | Composants React, AuthContext |
| CI automatique | GitHub Actions | Exécution à chaque push |

## X.2 Tests unitaires backend

### Fichiers

| Fichier | Classe testée | Cas couverts |
|---|---|---|
| `tests/Unit/Service/AnimalServiceTest.php` | `AnimalService` | create() — validation nom/espèce manquant, création réussie, mise à jour, suppression, accès refusé |
| `tests/Unit/Service/EvenementServiceTest.php` | `EvenementService` | create() — validation date manquante, accès refusé, création réussie |
| `tests/Unit/Service/PartageAnimalServiceTest.php` | `PartageAnimalService` | create() — doublon partage, création réussie |
| `tests/Unit/Service/MailerServiceTest.php` | `MailerService` | sendWelcomeEmail, sendReminderEmail, sendInvitationEmail |
| `tests/Unit/Entity/UserTest.php` | `User` | getRoles() — ROLE_USER toujours présent, getUserIdentifier() |

**Caractéristiques :** Toutes les dépendances (EntityManager, Repository) sont mockées. Aucune base de données requise. Tests rapides (< 1 s).

## X.3 Tests fonctionnels backend

### Fichiers

| Fichier | Routes testées |
|---|---|
| `tests/Functional/SecurityControllerTest.php` | POST `/api/register`, POST `/api/auth/login_check`, GET/PUT/DELETE `/api/me`, POST `/api/auth/reset-password` |
| `tests/Functional/AnimalControllerTest.php` | GET `/api/animals`, POST `/api/animals`, PUT `/api/animals/{id}`, DELETE `/api/animals/{id}` |
| `tests/Functional/JwtSecurityTest.php` | Accès sans token (401), token invalide (401), accès autorisé (200) |

**Configuration :** Base SQLite en mémoire (`.env.test`), clés JWT RSA générées pour le test, schéma Doctrine recréé à chaque test.

## X.4 Tests frontend

### Fichiers

| Fichier | Composant/Hook testé | Cas couverts |
|---|---|---|
| `src/context/__tests__/AuthContext.test.jsx` | `AuthContext` | login() — token stocké, user chargé ; logout() — token supprimé |
| `src/pages/__tests__/Login.test.jsx` | `Login` page | Rendu du formulaire, erreur identifiants invalides |
| `src/pages/__tests__/Register.test.jsx` | `Register` page | Rendu du formulaire, validation champs requis |

**Framework :** Vitest + React Testing Library + jsdom.

## X.5 Résultats CI (dernier run)

La CI GitHub Actions passe sur les branches `main` et `develop` avec les jobs suivants :

| Job | Statut | Description |
|---|---|---|
| PHPUnit — tests unitaires | ✅ PASS | PHP 8.4 |
| PHPUnit — tests fonctionnels | ✅ PASS | PHP 8.4, SQLite |
| PHP Lint | ✅ PASS | Syntaxe PHP |
| Vitest — tests React | ✅ PASS | Node 20 & 22 |
| Build production React | ✅ PASS | Node 20 & 22 |

---

# Chapitre XI — Déploiement

## XI.1 Prérequis

| Outil | Version minimale |
|---|---|
| Docker Desktop | 4.x |
| Docker Compose | 2.x (inclus) |
| Git | 2.x |

## XI.2 Installation pas à pas

### Étape 1 — Récupérer le code

```bash
git clone https://github.com/Nastry69/PetCare.git
cd PetCare
```

### Étape 2 — Variables d'environnement

```bash
cp .env.example .env
```

Éditer `.env` et renseigner :

```env
APP_ENV=prod
APP_SECRET=<openssl rand -hex 32>
POSTGRES_PASSWORD=<mot_de_passe_fort>
JWT_PASSPHRASE=<openssl rand -hex 32>
MAILER_FROM_EMAIL=noreply@petcare.fr
GOOGLE_CLIENT_ID=<votre_client_id>       # optionnel
GOOGLE_CLIENT_SECRET=<votre_secret>      # optionnel
```

### Étape 3 — Générer les clés JWT

```bash
mkdir -p backend/config/jwt
openssl genrsa -passout pass:VOTRE_PASSPHRASE -out backend/config/jwt/private.pem 2048
openssl rsa -passin pass:VOTRE_PASSPHRASE -in backend/config/jwt/private.pem -pubout \
  -out backend/config/jwt/public.pem
```

### Étape 4 — Démarrer la stack

```bash
docker compose up -d --build
```

### Étape 5 — Initialiser la base de données

```bash
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

### Étape 6 — Vérifier les services

```bash
docker compose ps
```

Tous les services doivent être à l'état **Up**.

## XI.3 URLs d'accès

| Service | URL |
|---|---|
| Application React | http://localhost:3000 |
| API Symfony | http://localhost:8000/api |
| Mailpit (emails) | http://localhost:8025 |

## XI.4 Infrastructure Docker

```yaml
services:
  app:       PHP 8.4-FPM — Symfony 7.4
  nginx:     Nginx 1.27 — reverse proxy API (port 8000)
  frontend:  React build de prod — nginx (port 3000)
  database:  PostgreSQL 16 — données persistées en volume
  mailer:    Mailpit — SMTP de développement
```

**Volumes persistants :**
- `database_data` — données PostgreSQL
- `uploads_data` — photos uploadées (animaux, profils)

## XI.5 Rappels automatiques (CRON)

Le Scheduler Symfony envoie automatiquement les emails de rappel chaque matin à 8h00. La tâche est définie dans `backend/src/Schedule.php` :

```
0 8 * * *  → DailyReminderMessage → MailerService::sendReminderEmail()
```

Pour déclencher manuellement :

```bash
docker compose exec app php bin/console app:send-reminders
```

## XI.6 Commandes de maintenance

```bash
# Migrations BDD
docker compose exec app php bin/console doctrine:migrations:migrate

# Vider le cache Symfony
docker compose exec app php bin/console cache:clear

# Logs en temps réel
docker compose logs -f app

# Tests PHPUnit
docker compose exec app vendor/bin/phpunit

# Arrêt propre
docker compose down
```

## XI.7 Tag de version

Le code de la livraison finale est taggé `v1.0` sur GitHub :

```bash
git tag -a v1.0 -m "PetCare v1.0 - Livraison finale Jalon 6"
git push origin v1.0
```

---

*Rapport généré pour le Jalon 6 — Juin 2026*  
*Formation CDA IPSSI — Tristan DZIOCH*
