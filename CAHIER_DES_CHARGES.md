# Cahier des Charges — EMSP Assignment Manager

**Projet :** Plateforme de gestion académique des exercices, groupes, soumissions, corrections et consultation communautaire  
**Contexte :** DSER (Digitalization of Services) — EMSP (École Multinationale Supérieure des Postes)  
**Date :** Juin 2026  
**Version :** 1.0  

---

## 1. Périmètre du projet

### 1.1 Objectifs

L'application permet de digitaliser la gestion des exercices pédagogiques à l'EMSP en remplaçant les processus manuels (emails, fichiers Excel, impressions PDF) par une plateforme web centralisée avec authentification, rôles, dépôt de projets, notation, publication des notes et espace communautaire public.

### 1.2 Acteurs concernés

| Rôle | Description |
|---|---|
| `admin` | Administrateur système — gestion complète des utilisateurs, exercices, groupes, imports/exports |
| `prof` | Professeur — notation, publication des notes, résolution de demandes de mot de passe, dérogations |
| `etudiant` | Étudiant — consultation des exercices, soumission de projets, visualisation des notes, galerie communautaire |
| `visiteur` | Visiteur anonyme — consultation de la galerie communautaire publique, commentaires avec pseudonyme |

---

## 2. Exigences fonctionnelles

### 2.1 Authentification et gestion des sessions

- Connexion par email + mot de passe avec vérification `password_verify()` et hachage bcrypt (`PASSWORD_DEFAULT`)
- Déconnexion sécurisée avec destruction complète de la session et suppression du cookie de session
- Forçage du changement de mot de passe à la première connexion (`first_login = 1`)
- Régénération de l'identifiant de session après connexion (`session_regenerate_id(true)`) — prévention du session fixation
- Demande de réinitialisation de mot de passe via formulaire (sans envoi d'email) créant une entrée en attente dans `password_reset_requests`
- Résolution de la demande par un admin ou un professeur avec génération d'un nouveau mot de passe temporaire
- Protection CSRF sur 100 % des formulaires POST (token par formulaire avec `global` comme clé par défaut)
- Pas de limite de tentatives de connexion (**à améliorer** — cf. §7)

### 2.2 Gestion des exercices (admin)

- CRUD complet des exercices (titre, type `individuel`/`groupe`, date limite, blocage, archivage)
- Un exercice ne peut pas être créé avec une date limite déjà passée
- Archivage progressif — un exercice archivé n'apparaît plus dans les listes actives mais reste en base
- Blocage d'un exercice — empêche toute soumission supplémentaire
- Association d'un professeur responsable à un exercice
- Création d'un professeur "à la volée" pendant la création d'un exercice, avec mot de passe temporaire affiché en modal
- Gestion des thèmes associés à chaque exercice (CRUD)
- Désignation des chefs de groupe par exercice (`exercice_designated_chefs`)
- Attribution de dérogations (extensions de délai) à un étudiant ou à un groupe entier

### 2.3 Gestion des utilisateurs (admin)

- CRUD complet des étudiants, professeurs et administrateurs
- Import massif d'étudiants depuis un fichier CSV/TXT (format `Prenom Nom` par ligne)
- Génération automatique d'identifiants lors de l'import : `prenom.nom@emsp.ci`
- Téléchargement du CSV des identifiants (mot de passe en clair stocké dans `import_identifiants` — **à sécuriser**)
- Export global des notes de tous les exercices (TXT et CSV)
- Export des notes par exercice (TXT et CSV, par admin et par professeur)

### 2.4 Soumission de projets (étudiant)

- Formulaire de soumission avec : titre du projet, choix du thème, lien URL (validé par `FILTER_VALIDATE_URL`), codes d'accès, explications, cahier des charges PDF
- Upload de PDF uniquement, vérification MIME par `finfo`, extension `.pdf` autorisée uniquement
- Pour les exercices de groupe : constitution du groupe avec chef obligatoire, sélection des membres parmi les étudiants disponibles
- Le chef de groupe est automatiquement ajouté et ne peut pas être sélectionné deux fois
- Un étudiant déjà dans un groupe ne peut pas être ajouté à un autre groupe du même exercice
- Mise à jour possible tant que le projet n'est pas noté ET que les notes ne sont pas publiées
- Notification au professeur responsable après soumission

### 2.5 Correction et notation (professeur)

- Deux modes de notation :
  - **Par critères** : Design (/5), Qualité du code (/5), Fonctionnel (/10) → total sur 20
  - **Globale** : note unique sur 20
- Saisie d'une critique/feedback textuel pour chaque notation
- Création de projets "placeholder" pour noter des étudiants qui n'ont pas soumis
- Publication des notes exercice par exercice (bascule `notes_publiees`)
- Rappel modal après notation invitant à publier ou garder en brouillon
- Statistiques par exercice : soumis, non soumis, en retard, en attente de correction
- Validation finale des corrections (fermeture de l'exercice)

### 2.6 Espace communautaire (public)

- Galerie publique des projets dont l'exercice est dépassé (date limite) ou bloqué
- Filtres par exercice, thème et type (individuel/groupe)
- Consultation détaillée d'un projet : métadonnées, cahier des charges en modal, téléchargement PDF, lien de test
- Commentaires anonymes avec pseudonyme obligatoire
- Réponses officielles réservées au responsable du projet (chef de groupe ou auteur individuel)
- Anti-spam : champ honeypot, délai minimal de 20 secondes par projet par session, limite de longueur
- Notation de l'utilité par les pairs (dépôts de commentaires)

### 2.7 Notifications

- Cloche de notifications dans la barre de navigation pour les utilisateurs connectés
- Événements notifiés :
  - Création d'exercice
  - Assignation d'un professeur
  - Désignation d'un chef de groupe
  - Soumission ou mise à jour d'un rendu
  - Correction ou publication de note
  - Dérogation accordée
  - corrections finalisées
  - Commentaire ajouté sur un projet
- Marquage global comme lu

### 2.8 Groupes

- Création de groupes par exercice (nom optionnel, chef obligatoire)
- Ajout/suppression de membres
- Empêchement des doublons (étudiant déjà dans un groupe du même exercice)
- Le chef ne peut pas être son propre membre

---

## 3. Exigences non fonctionnelles

### 3.1 Architecture

- Architecture MVC PHP pure, sans framework
- Front controller unique (`public/index.php`)
- Autoloading PSR-4 (`App\` namespace)
- Routage par registre centralisé (`app/routes.php`)
- Principes PRG (Post-Redirect-Get) pour toutes les actions POST
- Messages flash pour la rétroaction utilisateur (success/error)

### 3.2 Sécurité

- Toutes les sorties utilisateur encodées via `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` dans les vues
- Toutes les requêtes SQL utilisent des prepared statements PDO avec paramètres liés
- Transactions PDO pour les opérations multi-tables (suppressions en cascade, création de groupe)
- Protection CSRF sur tous les formulaires POST
- Headers HTTP de sécurité :
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: SAMEORIGIN`
  - `X-XSS-Protection: 1; mode=block`
- Cookies de session configurés en `HttpOnly`, `SameSite=Lax`, `Secure` si HTTPS détecté
- Validation côté serveur systématique (ne pas se fier uniquement à la validation client)

### 3.3 Performance

- Génération de PDF via bibliothèque FPDF
- Pagination sur toutes les listes d'utilisateurs et d'exercices (25 éléments par page)
- Limite de 100 notifications par requête pour `listForUser()`

### 3.4 Compatibilité

- PHP 8.x avec `declare(strict_types=1)` sur tous les fichiers
- MySQL/MariaDB avec moteur InnoDB, encodage `utf8mb4_unicode_ci`
- Navigateurs modernes (Bootstrap 5.3)
- Windows (XAMPP) en environnement de développement

---

## 4. Modèle de données

### 4.1 Schéma relationnel

```
utilisateurs (id_user PK)
  ├─ exercices (professeur_id FK → id_user)
  ├─ projets (id_user_individuel FK → id_user, id_groupe FK → groupes)
  ├─ derogations (id_user FK → id_user, id_groupe FK → groupes)
  ├─ exercice_designated_chefs (id_user FK → id_user)
  ├─ membres_groupe (id_user FK → id_user, id_groupe FK → groupes)
  ├─ import_identifiants (id_user FK → id_user)
  ├─ password_reset_requests (id_user FK → id_user, resolved_by FK → id_user)
  └─ notifications (id_user FK → id_user)

exercices (id_exercice PK)
  ├─ themes (id_exercice FK)
  ├─ groupes (id_exercice FK, id_chef FK → utilisateurs)
  ├─ derogations (id_exercice FK)
  ├─ exercice_designated_chefs (id_exercice FK)
  └─ projets (id_exercice FK, id_theme FK → themes)

groupes (id_groupe PK)
  ├─ membres_groupe (id_groupe FK)
  ├─ derogations (id_groupe FK)
  └─ projets (id_groupe FK)

projets (id_projet PK)
  ├─ commentaires (id_projet FK)
  └─ notifications (via exercice)

derogations (id_derogation PK)
  ├─ id_exercice FK → exercices
  ├─ id_user FK → utilisateurs (nullable)
  └─ id_groupe FK → groupes (nullable)

commentaires (id_commentaire PK)
  ├─ id_projet FK → projets
  ├─ id_user FK → utilisateurs (nullable)
  └─ parent_id FK → commentaires (auto-référence, threaded)

notifications (id_notification PK)
  └─ id_user FK → utilisateurs

import_identifiants (id_import PK)
  └─ id_user FK → utilisateurs (stockage mot de passe en clair — **amélioration nécessaire**)

password_reset_requests (id_request PK)
  ├─ id_user FK → utilisateurs
  └─ resolved_by FK → utilisateurs (nullable)
```

### 4.2 Tables et colonnes principales

| Table | Clé Primaire | Rôle |
|---|---|---|
| `utilisateurs` | `id_user` | Utilisateurs (admin, prof, etudiant) |
| `exercices` | `id_exercice` | Exercices/assignments |
| `themes` | `id_theme` | Thèmes par exercice |
| `groupes` | `id_groupe` | Groupes d'étudiants |
| `membres_groupe` | `id_liaison` | Membres par groupe |
| `derogations` | `id_derogation` | Extensions de délai |
| `exercice_designated_chefs` | `id_liaison` | Chefs désignés par exercice |
| `projets` | `id_projet` | Soumissions de projets et notes |
| `commentaires` | `id_commentaire` | Commentaires communautaires |
| `notifications` | `id_notification` | Notifications in-app |
| `password_reset_requests` | `id_request` | Demandes de reset en attente |
| `import_identifiants` | `id_import` | Stockage temporaire identifiants import |

---

## 5. Règles métier détaillées

### 5.1 États d'un exercice

```
actif → [bloqué] → archivé
         ↓
   (bloqué = aucune soumission possible, consultation publique possible)
```

- `est_bloque = 0` : soumissions autorisées
- `est_bloque = 1` : soumissions fermées, projets deviennent publics
- `archive = 0` : exercice actif
- `archive = 1` : exercice archivé (plus visible dans les listes actives)

### 5.2 États d'un projet

```
[brouillon] → [notes_publiees = 0] → [notes_publiees = 1]
       ↓                                      ↓
  modifiable par étudiant              consultation publique
  si notes non publiées                galerie communautaire
```

### 5.3 Processus de notation

1. Le professeur accède à la liste des soumissions de l'exercice
2. Il choisit le mode : critères (5/5/10) ou global (X/20)
3. Il saisit la note + critique
4. Il peut créer un "placeholder" pour les étudiants non soumis
5. À la fin il reçoit un rappel modal : Publier les notes ou Garder en brouillon
6. Si publication : bascule `notes_publiees = 1` pour tous les projets notés de l'exercice
7. Les étudiants peuvent alors voir leurs notes et télécharger le rapport PDF

### 5.4 Attribution des notes

| Critère | Maximum | Champ BDD |
|---|---|---|
| Design | 5.00 | `note_design` |
| Qualité du code | 5.00 | `note_code` |
| Fonctionnel | 10.00 | `note_fonc` |
| **Note totale** | **20.00** | `note_totale` |

### 5.5 Composition des groupes

- Un groupe appartient à un seul exercice
- Un étudiant ne peut être que dans un seul groupe par exercice
- Le chef est obligatoire et ne peut pas être son propre membre
- Un étudiant non assigné à un groupe mais soumis individuellement est considéré comme "solo"

---

## 6. Routes API (URLs)

### 6.1 Authentification

| Méthode | URL | Contrôleur::Action |
|---|---|---|
| GET | `/auth/login` | `AuthController::showLogin` |
| POST | `/auth/login` | `AuthController::processLogin` |
| GET | `/auth/logout` | `AuthController::processLogout` |
| GET | `/auth/forgot-password` | `AuthController::showForgotPassword` |
| POST | `/auth/forgot-password` | `AuthController::processForgotPassword` |
| GET | `/auth/change-password` | `AuthController::showChangePassword` |
| POST | `/auth/change-password` | `AuthController::processChangePassword` |

### 6.2 Espace communautaire (public)

| Méthode | URL | Contrôleur::Action |
|---|---|---|
| GET | `/` | `CommunityController::index` |
| GET | `/community` | `CommunityController::index` |
| GET | `/community/project?id=` | `CommunityController::show` |
| POST | `/community/comment` | `CommunityController::comment` |
| POST | `/community/reply` | `CommunityController::reply` |

### 6.3 Étudiant (`role = etudiant`)

| Méthode | URL | Contrôleur::Action |
|---|---|---|
| GET | `/student/dashboard` | `StudentController::dashboard` |
| GET | `/student/submit?id=` | `StudentController::showSubmitForm` |
| POST | `/student/submit` | `StudentController::processSubmit` |
| GET | `/student/peer-gallery` | `StudentController::peerGallery` |
| POST | `/student/peer-gallery/comment` | `StudentController::addPeerComment` |
| GET | `/student/report-pdf` | `StudentController::downloadReportPdf` |
| GET | `/student/project/report-pdf?id=` | `StudentController::downloadProjectPdf` |

### 6.4 Professeur (`role = prof`)

| Méthode | URL | Contrôleur::Action |
|---|---|---|
| GET | `/prof/dashboard` | `ProfController::dashboard` |
| GET | `/prof/grade?exercise=&user=` | `ProfController::showGradeForm` |
| POST | `/prof/grade` | `ProfController::processGrade` |
| POST | `/prof/publish-notes` | `ProfController::publishNotes` |
| POST | `/prof/publish-prompt/dismiss` | `ProfController::dismissPublishPrompt` |
| POST | `/prof/password-reset/resolve` | `ProfController::resolvePasswordReset` |
| POST | `/prof/exercise/finish-corrections` | `ProfController::confirmCorrectionsFinished` |
| GET | `/prof/exercise/add-exemption` | `ProfController::showAddExemption` |
| POST | `/prof/exercise/add-exemption` | `ProfController::processAddExemption` |
| GET | `/prof/exercise/export-grades` | `ProfController::exportExerciseGrades` |
| GET | `/prof/exercises/export-all-grades` | `ProfController::exportAllExercisesGrades` |

### 6.5 Administrateur (`role = admin`)

| Méthode | URL | Contrôleur::Action |
|---|---|---|
| GET | `/admin/dashboard` | `AdminController::dashboard` |
| GET | `/admin/exercise/create` | `AdminController::showCreateExercise` |
| POST | `/admin/exercise/create` | `AdminController::processCreateExercise` |
| GET | `/admin/exercises` | `AdminController::listExercises` |
| GET | `/admin/exercises/edit?id=` | `AdminController::showEditExercise` |
| POST | `/admin/exercises/edit` | `AdminController::processUpdateExercise` |
| POST | `/admin/exercises/delete` | `AdminController::processDeleteExercise` |
| POST | `/admin/exercise/block` | `AdminController::processToggleBlock` |
| POST | `/admin/exercise/archive` | `AdminController::processToggleArchive` |
| POST | `/admin/reset-password/dismiss` | `AdminController::dismissResetPasswordResult` |
| POST | `/admin/password-reset/resolve` | `AdminController::resolvePasswordReset` |
| GET | `/admin/exercise/export-grades?id=` | `AdminController::exportExerciseGrades` |
| GET | `/admin/exercises/export-all-grades` | `AdminController::exportAllExercisesGrades` |
| GET | `/admin/group/manage?exercise=` | `AdminController::showManageGroups` |
| POST | `/admin/group/create` | `AdminController::processCreateGroup` |
| POST | `/admin/exemption/add` | `AdminController::processAddExemption` |
| GET | `/admin/students/import` | `AdminController::showImportStudents` |
| POST | `/admin/students/import` | `AdminController::processImportStudents` |
| GET | `/admin/students/import/download` | `AdminController::downloadImportCsv` |
| GET | `/admin/students/export` | `AdminController::exportAllStudentsCsv` |
| GET | `/admin/students` | `AdminController::listStudents` |
| GET | `/admin/students/create` | `AdminController::showCreateStudent` |
| POST | `/admin/students/create` | `AdminController::processCreateStudent` |
| GET | `/admin/students/edit?id=` | `AdminController::showEditStudent` |
| POST | `/admin/students/edit` | `AdminController::processUpdateStudent` |
| POST | `/admin/students/delete` | `AdminController::processDeleteStudent` |
| GET | `/admin/professors` | `AdminController::listProfessors` |
| GET | `/admin/admins` | `AdminController::listAdmins` |

### 6.6 Notifications

| Méthode | URL | Contrôleur::Action |
|---|---|---|
| GET | `/notifications` | `NotificationController::index` |
| POST | `/notifications/read-all` | `NotificationController::markAllRead` |

---

## 7. Exigences de sécurité

### 7.1 Mesures implémentées

| Mesure | Implémentation |
|---|---|
| Injection SQL | Prepared statements PDO — 100 % des requêtes |
| XSS | Échappement systématique via `htmlspecialchars()` dans les vues |
| CSRF | Token par formulaire vérifié sur tous les POST |
| Session fixation | `session_regenerate_id(true)` après login |
| Cookies session | `HttpOnly`, `SameSite=Lax`, `Secure` si HTTPS |
| Déconnexion | `Session::destroy()` avec suppression du cookie |
| Upload PDF | Validation MIME `application/pdf`, extension `.pdf` obligatoire |
| Honeypot anti-bot | Champ `website` caché dans les formulaires de commentaire |
| Rate limiting | 20 secondes minimum entre commentaires anonymes (par session/projet) |

### 7.2 Points d'attention identifiés

| Sévérité | Description | Fichier(s) |
|---|---|---|
| 🔴 **CRITIQUE** | Stockage de mots de passe en clair dans `import_identifiants` et `password_reset_requests.generated_password` | `User.php`, `PasswordResetRequest.php` |
| 🔴 **CRITIQUE** | Export CSV avec `COALESCE(..., 'password123')` exposant un mot de passe hardcodé pour tous les utilisateurs sans entrée `import_identifiants` | `AdminController.php` |
| 🟠 **HAUT** | Pas de rate-limiting sur le login — attaque par force brute possible | `AuthController.php` |
| 🟠 **HAUT** | Un professeur peut résoudre n'importe quelle demande de reset de mot de passe, sans vérification qu'elle concerne un étudiant de ses exercices | `ProfController.php` |
| 🟠 **HAUT** | Création manuelle d'étudiants avec mot de passe `password123` hardcodé | `AdminController.php` |
| 🟠 **HAUT** | Upload dans `public/uploads/` avec droits `0777` — fichiers accessibles directement par URL | `StudentController.php` |
| 🟡 **MOYEN** | Génération de mots de passe par `str_shuffle()` — pas un CSPRNG | `AdminController.php` |
| 🟡 **MOYEN** | Délai de commentaire contournable par changement de session/cookies | `CommunityController.php` |

---

## 8. Installation et déploiement

### 8.1 Prérequis

- PHP 8.x avec extensions PDO, mbstring
- MySQL/MariaDB
- XAMPP ou serveur web équivalent (Apache/Nginx)
- Navigateur moderne

### 8.2 Étapes d'installation

```text
1. Copier le projet dans le serveur web :
   C:\xampp\htdocs\COMPO-FINAL\php-mvc-assignment-manager

2. Vérifier la configuration dans student-hub-php/config/config.php :
   - DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME

3. Importer le schéma dans phpMyAdmin :
   student-hub-php/database.sql

4. Accéder à l'application :
   http://localhost/COMPO-FINAL/php-mvc-assignment-manager/student-hub-php/public/
```

### 8.3 Base de données vide (optionnel)

Pour repartir d'une base vide avec uniquement l'administrateur :

```sql
USE emsp_assignment_db;

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE notifications;
TRUNCATE TABLE password_reset_requests;
TRUNCATE TABLE commentaires;
TRUNCATE TABLE projets;
TRUNCATE TABLE derogations;
TRUNCATE TABLE membres_groupe;
TRUNCATE TABLE groupes;
TRUNCATE TABLE exercice_designated_chefs;
TRUNCATE TABLE themes;
TRUNCATE TABLE exercices;
TRUNCATE TABLE import_identifiants;
TRUNCATE TABLE utilisateurs;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO utilisateurs (id_user, nom, prenom, email, mot_de_passe, role, first_login)
VALUES (1, 'Ivo', 'Abdoul', 'abdoulivo.ci', '$2y$10$pXe/6v4UaJUT9EPSi68dp.dBkPyg5QiC0ofHHMq4f.HzZ6HPfV7zS', 'admin', 0);
```

### 8.4 Migrations

Pour les bases existantes, exécuter `migrate.sql` qui ajoute :
- `exercices.archive`
- `utilisateurs.first_login`
- `exercice_designated_chefs`
- `password_reset_requests`
- colonnes communautaires de `commentaires`
- table `notifications`
- nettoyage des notes globales

### 8.5 Vérification post-installation

```powershell
# Vérification syntaxe PHP
C:\xampp\php\php.exe -l student-hub-php\app\Controllers\*.php
C:\xampp\php\php.exe -l student-hub-php\app\Models\*.php
C:\xampp\php\php.exe -l student-hub-php\public\index.php
```

### 8.6 Comptes de test

| Rôle | Email | Mot de passe |
|---|---|---|
| Admin | `admin@emsp.ci` | `password123` |
| Professeur | `amadou@emsp.ci` | `password123` |
| Étudiant | `jean.koffi@emsp.ci` | `password123` |

---

## 9. Structure technique

### 9.1 Arborescence backend

```
student-hub-php/
├── .htaccess
├── database.sql                 # Schéma complet
├── migrate.sql                  # Scripts de migration
├── config/
│   └── config.php               # Constantes DB et URL
├── public/
│   ├── index.php                # Front controller
│   ├── lib/
│   │   └── fpdf.php             # Librairie FPDF
│   ├── uploads/                 # Cahiers des charges PDF (web-accessibles)
│   └── template/                # Assets Bootstrap locaux
├── app/
│   ├── routes.php               # Registre central des routes
│   ├── Core/
│   │   ├── Controller.php       # Classe abstraction contrôleur (render, redirect, json, authorize)
│   │   ├── Router.php           # Routeur GET/POST, rendu 404
│   │   ├── Database.php         # Singleton PDO
│   │   ├── Session.php          # Wrapper session sécurisé (HttpOnly, SameSite, flash)
│   │   ├── Csrf.php             # Génération et vérification de tokens CSRF
│   │   └── PdfReport.php        # Générateur de PDF via FPDF
│   ├── Controllers/
│   │   ├── AuthController.php   # Login, logout, changement/oubli de mot de passe
│   │   ├── AdminController.php  # Toute la logique admin (1 368 lignes)
│   │   ├── ProfController.php   # Notation, publication, exports, dérogations
│   │   ├── StudentController.php # Soumission, galerie, téléchargement PDF
│   │   ├── CommunityController.php # Galerie publique, commentaires, réponses
│   │   └── NotificationController.php # Notifications utilisateur
│   ├── Models/
│   │   ├── User.php             # CRUD utilisateurs, recherche par rôle
│   │   ├── Exercise.php         # Gestion exercices, thèmes, chefs, dérogations, blocage
│   │   ├── Group.php            # Groupes, membres, soumissions manquantes
│   │   ├── Project.php          # Soumissions, notation, publication, galerie publique
│   │   ├── Comment.php          # Commentaires avec threads (parent_id)
│   │   ├── Notification.php     # CRUD notifications
│   │   └── PasswordResetRequest.php # Demandes de reset (pending → resolved)
│   └── Views/
│       ├── layout/main.php      # Layout Bootstrap principal
│       ├── auth/                # login, forgot_password, change_password
│       ├── admin/               # dashboard, exercises, students, groups, forms
│       ├── prof/                # dashboard, grade, exemption
│       ├── student/             # dashboard, submit, peer_gallery
│       ├── community/           # index, show
│       └── notifications/       # index
└── docs/
    ├── README.md
    ├── INSTALL.md
    ├── DATABASE.md
    ├── ROUTES.md
    ├── MODELS.md
    ├── CONTROLLERS.md
    └── FLUX.md
```

### 9.2 Arborescence frontend

```
src/
├── App.tsx              # Application React monolithique (~1 600 lignes)
├── main.tsx             # Point d'entrée
├── index.css            # Tailwind CSS v4 (@tailwindcss/vite)
├── types.ts             # Interfaces TypeScript (DbUser, DbExercise, …)
└── phpCodeData.ts       # Données PHP embarquées pour explorateur de code
```

### 9.3 Stack technique

| Couche | Technologie |
|---|---|
| Backend | PHP 8.x, PDO, MVC maison |
| Frontend | React 19, TypeScript, Tailwind CSS v4, Vite 6 |
| Base de données | MySQL/MariaDB, InnoDB, utf8mb4_unicode_ci |
| PDF | FPDF |
| UI | Bootstrap 5.3 (vues PHP), Tailwind CSS v4 (simulateur React) |
| Authentification | Sessions PHP, bcrypt (PASSWORD_DEFAULT) |

---

## 10. Endpoints et livrables

### 10.1 Livrables attendus

| # | Livrable | Description |
|---|---|---|
| 1 | Application web déployable | Code source complet, fonctionnel sur XAMPP |
| 2 | Base de données | `database.sql` + scripts `migrate.sql` |
| 3 | Documentation d'installation | `DEPLOIEMENT_EMSP.md` |
| 4 | Documentation technique | 7 fichiers Markdown dans `student-hub-php/docs/` |
| 5 | Comptes de test | 3 comptes préconfigurés (admin, prof, étudiant) |

### 10.2 KPIs et critères d'acceptation

- [ ] Installation fonctionnelle en moins de 10 minutes (import SQL + accès URL)
- [ ] Connexion réussie pour chaque rôle (admin, prof, étudiant)
- [ ] CRUD exercices sans erreur
- [ ] Import CSV d'au moins 50 étudiants
- [ ] Soumission de projet individuel et de groupe
- [ ] Notation par critères et notation globale
- [ ] Publication des notes et consultation par étudiant
- [ ] Espace communautaire accessible publiquement
- [ ] Commentaires et réponses fonctionnels
- [ ] Exports TXT et CSV des notes

---

## 11. dette technique et améliorations futures

### 11.1 Réorganisations recommandées

| Priorité | Action | Bénéfice |
|---|---|---|
| Haute | Découper `AdminController` (1 368 lignes) en ≥ 5 contrôleurs dédiés | Maintenabilité, testabilité |
| Haute | Extraire les 4 exports de notes en service partagé (Admin + Prof) | DRY, réduction de duplication |
| Haute | Corriger le stockage en clair des mots de passe (`import_identifiants`, `password_reset_requests`) | Sécurité critique |
| Moyenne | Ajouter rate-limiting / brute-force protection sur login | Sécurité |
| Moyenne | Corriger le bug CSV import (ordre Nom/Prenom inversé silencieusement) | Fiabilité |
| Moyenne | Corriger `getMissingSubmissionsForExercise()` pour les exercices individuels | Correction |
| Moyenne | Déplacer `public/uploads/` hors de la racine web + droits `0755` | Sécurité |
| Basse | Remplacer `str_shuffle()` par `random_int()` / `bin2hex(random_bytes())` | Sécurité |

### 11.2 Bugs connus

| # | Description | Fichier |
|---|---|---|
| 1 | `getMissingSubmissionsForExercise()` retourne toujours vide pour exercices individuels | `Group.php` |
| 2 | `getMembers()` peut dupliquer le chef de groupe dans le UNION | `Group.php` |
| 3 | Double vérification de `est_bloque` dupliquée dans `checkSubmissionStatus()` | `Exercise.php` |
| 4 | `gradeGlobal()` ne fait pas `ROUND()` sur `note_totale` | `Project.php` |
| 5 | `showSubmitForm()` bloque l'édition si `note_totale IS NOT NULL` même si notes non publiées | `StudentController.php` |
| 6 | Prof peut résoudre n'importe quelle demande de reset (pas de filtre par exercice) | `ProfController.php` |

---

## 12. Annexes

### 12.1 Variables de configuration (`config.php`)

| Constante | Valeur par défaut | Description |
|---|---|---|
| `DB_HOST` | `localhost` | Hôte MySQL |
| `DB_PORT` | `3306` | Port MySQL |
| `DB_USER` | `root` | Utilisateur MySQL |
| `DB_PASS` | `''` | Mot de passe MySQL |
| `DB_NAME` | `emsp_assignment_db` | Nom de la base |
| `APP_NAME` | `EMSP Assignment Manager` | Nom de l'application |
| `APP_URL` | Détecté dynamiquement | URL de base |
| `SESSION_LIFETIME` | `3600` | Durée de session (secondes) |
| `APP_PATH` | `dirname(__DIR__) . '/app'` | Chemin de l'application |

### 12.2 Comptes pré-seedés (`database.sql`)

| id_user | Nom | Prénom | Email | Rôle |
|---|---|---|---|---|
| 1 | Admin | Ivob | admin@emsp.ci | admin |
| 2 | Amadou | M. | amadou@emsp.ci | prof |
| 3 | Koffi | Jean | jean.koffi@emsp.ci | etudiant |
| 4 | Traore | Fatoumata | fatou.traore@emsp.ci | etudiant |
| 5 | Diallo | Mamadou | mamadou.diallo@emsp.ci | etudiant |
| 6 | Bamba | Bakary | bakary.bamba@emsp.ci | etudiant |
| 7 | Gomez | Marie | marie.gomez@emsp.ci | etudiant |

Mot de passe par défaut (tous) : `password123` (hash bcrypt stocké en base)

### 12.3 Normes de codage

- `declare(strict_types=1)` sur tous les fichiers PHP
- Conventions de nommage :
  - Classes : `PascalCase`
  - Méthodes : `camelCase`
  - Tables BDD : `snake_case` pluriel
  - Routes : `kebab-case` RESTful
- Indentation : espaces (pas de tabs — cohérence Bootstrap)
- Encodage : UTF-8 pour toutes les vues, `utf8mb4_unicode_ci` pour la base
