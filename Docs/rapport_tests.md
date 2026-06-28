# PetCare — Rapport de Tests
## Jalon 5 & 6 — Mai / Juin 2026

**Auteur :** Tristan DZIOCH  
**Projet :** PetCare — Formation CDA IPSSI  
**Stack de test :** PHPUnit 11 (backend) + Vitest (frontend)

---

## 1. Vue d'ensemble

| Catégorie | Fichiers | Résultat CI |
|---|---|---|
| Tests unitaires backend | 5 fichiers, ~30 cas | ✅ PASS |
| Tests fonctionnels backend | 3 fichiers, ~20 cas | ✅ PASS |
| Tests unitaires frontend | 3 fichiers, ~10 cas | ✅ PASS |
| Build production React | — | ✅ PASS |
| Lint PHP | — | ✅ PASS |

---

## 2. Tests Unitaires Backend (PHPUnit)

### Configuration

- **Framework :** PHPUnit 11
- **Base de données :** aucune (mocks uniquement)
- **Environnement :** `.env.test` avec `APP_ENV=test`
- **Commande :** `vendor/bin/phpunit tests/Unit`

### AnimalServiceTest — `tests/Unit/Service/AnimalServiceTest.php`

| Test | Description | Résultat |
|---|---|---|
| `testCreateThrowsWhenNomIsMissing` | Création sans `nom` → `InvalidArgumentException` | ✅ PASS |
| `testCreateThrowsWhenEspeceIsMissing` | Création sans `espece` → `InvalidArgumentException` | ✅ PASS |
| `testCreateSuccess` | Création valide avec user propriétaire | ✅ PASS |
| `testUpdateThrowsWhenNotFound` | Update sur ID inexistant → `RuntimeException` | ✅ PASS |
| `testUpdateThrowsWhenNotOwner` | Update par un non-propriétaire → `RuntimeException` | ✅ PASS |
| `testDeleteThrowsWhenNotOwner` | Suppression par non-propriétaire → `RuntimeException` | ✅ PASS |

### EvenementServiceTest — `tests/Unit/Service/EvenementServiceTest.php`

| Test | Description | Résultat |
|---|---|---|
| `testCreateThrowsWhenDateMissing` | Création sans date → `InvalidArgumentException` | ✅ PASS |
| `testCreateThrowsWhenAccessDenied` | Création sur animal non accessible → `RuntimeException` | ✅ PASS |
| `testCreateSuccess` | Création valide avec rappel activé | ✅ PASS |

### PartageAnimalServiceTest — `tests/Unit/Service/PartageAnimalServiceTest.php`

| Test | Description | Résultat |
|---|---|---|
| `testCreateThrowsOnDuplicate` | Double partage même animal/utilisateur → `RuntimeException` | ✅ PASS |
| `testCreateSuccess` | Partage valide avec rôle 'lecture' | ✅ PASS |

### MailerServiceTest — `tests/Unit/Service/MailerServiceTest.php`

| Test | Description | Résultat |
|---|---|---|
| `testSendWelcomeEmail` | Email de bienvenue envoyé via MailerInterface mocké | ✅ PASS |
| `testSendReminderEmail` | Email de rappel avec données événement | ✅ PASS |
| `testSendInvitationEmail` | Email d'invitation avec rôle | ✅ PASS |

### UserTest — `tests/Unit/Entity/UserTest.php`

| Test | Description | Résultat |
|---|---|---|
| `testGetRolesAlwaysContainsRoleUser` | `getRoles()` contient toujours `ROLE_USER` | ✅ PASS |
| `testGetUserIdentifier` | `getUserIdentifier()` retourne l'email | ✅ PASS |

---

## 3. Tests Fonctionnels Backend (PHPUnit + WebTestCase)

### Configuration

- **Framework :** PHPUnit 11 + Symfony WebTestCase
- **Base de données :** SQLite en mémoire (schema recréé à chaque test)
- **JWT :** clés RSA générées dynamiquement pour les tests
- **Commande :** `vendor/bin/phpunit tests/Functional`

### SecurityControllerTest — `tests/Functional/SecurityControllerTest.php`

| Test | Route | Code attendu | Résultat |
|---|---|---|---|
| `testRegisterSuccess` | POST `/api/register` | 201 + token JWT | ✅ PASS |
| `testRegisterDuplicateEmail` | POST `/api/register` | 409 | ✅ PASS |
| `testRegisterMissingField` | POST `/api/register` | 400 | ✅ PASS |
| `testLoginSuccess` | POST `/api/auth/login_check` | 200 + token | ✅ PASS |
| `testLoginInvalidPassword` | POST `/api/auth/login_check` | 401 | ✅ PASS |
| `testGetMe` | GET `/api/me` | 200 + user data | ✅ PASS |
| `testUpdateMe` | PUT `/api/me` | 200 + user mis à jour | ✅ PASS |
| `testDeleteMe` | DELETE `/api/me` | 200 | ✅ PASS |

### AnimalControllerTest — `tests/Functional/AnimalControllerTest.php`

| Test | Route | Code attendu | Résultat |
|---|---|---|---|
| `testListAnimals` | GET `/api/animals` | 200 + tableau | ✅ PASS |
| `testCreateAnimal` | POST `/api/animals` | 201 + animal | ✅ PASS |
| `testCreateAnimalMissingName` | POST `/api/animals` | 400 | ✅ PASS |
| `testUpdateAnimal` | PUT `/api/animals/{id}` | 200 | ✅ PASS |
| `testDeleteAnimal` | DELETE `/api/animals/{id}` | 200 | ✅ PASS |
| `testAccessOtherUserAnimal` | GET `/api/animals/{id}` | 403 | ✅ PASS |

### JwtSecurityTest — `tests/Functional/JwtSecurityTest.php`

| Test | Scénario | Code attendu | Résultat |
|---|---|---|---|
| `testUnauthenticatedAccess` | GET `/api/animals` sans token | 401 | ✅ PASS |
| `testInvalidToken` | GET `/api/animals` token invalide | 401 | ✅ PASS |
| `testExpiredToken` | GET `/api/animals` token expiré | 401 | ✅ PASS |
| `testValidToken` | GET `/api/animals` token valide | 200 | ✅ PASS |

---

## 4. Tests Frontend (Vitest)

### Configuration

- **Framework :** Vitest + React Testing Library + jsdom
- **Commande :** `npm test` (dans `frontend/`)

### AuthContext.test.jsx — `src/context/__tests__/AuthContext.test.jsx`

| Test | Description | Résultat |
|---|---|---|
| `login stores token in localStorage` | Token JWT sauvegardé après login réussi | ✅ PASS |
| `logout removes token from localStorage` | Token supprimé après logout | ✅ PASS |
| `user is loaded from API on mount if token exists` | `GET /me` appelé si token présent | ✅ PASS |

### Login.test.jsx — `src/pages/__tests__/Login.test.jsx`

| Test | Description | Résultat |
|---|---|---|
| `renders login form` | Champs email, password et bouton présents | ✅ PASS |
| `shows error on invalid credentials` | Message d'erreur affiché en 401 | ✅ PASS |

### Register.test.jsx — `src/pages/__tests__/Register.test.jsx`

| Test | Description | Résultat |
|---|---|---|
| `renders register form` | Champs nom, prénom, email, password présents | ✅ PASS |
| `shows error on existing email` | Message d'erreur affiché en 409 | ✅ PASS |

---

## 5. Pipeline CI (GitHub Actions)

Fichier : `.github/workflows/ci.yml`  
Déclenchement : push sur `main` et `develop`, pull requests.

### Jobs

| Job | Environnement | Résultat |
|---|---|---|
| `backend-tests` (PHPUnit unitaires) | PHP 8.4 / ubuntu | ✅ PASS |
| `backend-tests` (PHPUnit fonctionnels) | PHP 8.4 / ubuntu / SQLite | ✅ PASS |
| `backend-lint` | PHP 8.4 | ✅ PASS |
| `frontend-build` (Vitest) | Node 20 | ✅ PASS |
| `frontend-build` (build prod) | Node 20 | ✅ PASS |
| `frontend-build` (Vitest) | Node 22 | ✅ PASS |
| `frontend-build` (build prod) | Node 22 | ✅ PASS |

---

## 6. Analyse de couverture

La couverture formelle (Xdebug) n'a pas été générée dans l'environnement CI pour éviter un ralentissement excessif. Les composants critiques testés sont :

| Composant | Couvert | Type de test |
|---|---|---|
| `AnimalService` | ✅ | Unitaire |
| `EvenementService` | ✅ | Unitaire |
| `PartageAnimalService` | ✅ | Unitaire |
| `MailerService` | ✅ | Unitaire |
| `User` (entity) | ✅ | Unitaire |
| `SecurityController` | ✅ | Fonctionnel |
| `AnimalController` | ✅ | Fonctionnel |
| `JwtSecurity` | ✅ | Fonctionnel |
| `AuthContext` | ✅ | Frontend |
| `Login` page | ✅ | Frontend |
| `Register` page | ✅ | Frontend |

---

*Rapport de tests — Jalon 6 — Juin 2026 — Tristan DZIOCH*
