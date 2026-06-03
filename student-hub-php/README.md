# EMSP Assignment Manager - PHP MVC PRG

Plateforme academique DSER pour gerer les exercices, les groupes, les soumissions, les corrections, les notes, les derogations et la consultation communautaire des projets.

Le projet est code en PHP pur oriente objet, structure en MVC avec un front controller, des requetes PDO preparees, une protection CSRF et un flux PRG pour les actions POST.

## Architecture

```text
student-hub-php/
  app/
    Controllers/      Controleurs Auth, Admin, Student, Prof, Community, Notification
    Core/             Router, Controller, Session, CSRF, Database
    Models/           Requetes PDO et logique donnees
    Views/            Vues Bootstrap
    routes.php        Registre central des routes
  config/
    config.php        Configuration MySQL et URL de base
  public/
    index.php         Front controller
    uploads/          Cahiers des charges PDF
    template/         Bootstrap local, assets StartBootstrap, logo EMSP
  database.sql        Schema complet pour nouvelle installation
```

## Regles Metier

- Toutes les actions d'ecriture passent par POST avec CSRF et redirection PRG.
- Les imports et exports utilisent des messages flash/toasts pour confirmer l'action.
- Les cahiers des charges sont limites aux PDF et peuvent etre lus en modal ou telecharges.
- Les exercices ne peuvent pas etre crees avec une date limite passee.
- Pour un exercice de groupe, seul le chef de groupe peut soumettre.
- Le chef est automatiquement rattache a son groupe et ne doit pas se selectionner lui-meme.
- Les etudiants deja pris dans un groupe ne peuvent plus etre selectionnes pour un autre groupe du meme exercice.
- Un groupe peut etre forme avant la soumission du projet.
- Un projet deja note ou publie ne doit plus etre modifiable par l'etudiant.
- Le professeur peut noter en mode global ou par criteres, puis publier les notes.
- Une note enregistree reste en brouillon tant que le professeur ne publie pas les notes de l'exercice.
- Apres une notation, le professeur recoit un rappel modal lui proposant de publier les notes de l'exercice ou de garder le brouillon.
- Quand un professeur est cree pendant la creation d'un exercice, son mot de passe temporaire est affiche dans une modal de confirmation et n'est pas ecrit dans les logs.

## Dashboard Professeur

Le dashboard professeur est organise en niveaux clairs :

1. Tableau des exercices, du plus recent au plus ancien.
2. Bouton `Stats` pour les indicateurs et graphiques uniquement.
3. Bouton `Soumissions` pour ouvrir la liste des rendus dans une modal.
4. Bouton `Publier les notes` pour rendre visibles les notes corrigees de l'exercice.
5. Bouton `Exporter les notes de l'exercice` pour telecharger le rapport TXT de cet exercice.
6. Chaque soumission peut ouvrir une modal de details avec auteur, theme, lien de test, codes d'acces, notice, cahier des charges lisible en modal, telechargement PDF et action `Evaluer`.

## Espace Communautaire

Les projets deviennent consultables publiquement apres la date limite ou lorsque l'exercice est bloque/cloture.

Routes principales :

```text
GET  /community
GET  /community/project?id=...
POST /community/comment
POST /community/reply
```

Fonctionnalites :

- galerie publique des projets visibles ;
- filtres par exercice, theme et type ;
- lecture du cahier des charges PDF en modal ;
- telechargement PDF separe ;
- commentaire visiteur avec pseudonyme obligatoire ;
- reponse officielle reservee au responsable du projet ;
- anti-spam simple : honeypot, delai minimal, limite de longueur.

## Notifications

Une cloche est affichee dans le layout pour les utilisateurs connectes.

Routes principales :

```text
GET  /notifications
POST /notifications/read-all
```

Evenements notifies :

- creation d'exercice ;
- assignation d'un professeur ;
- designation d'un chef de groupe ;
- soumission ou mise a jour d'un rendu ;
- correction ou publication de note ;
- derogation accordee ;
- corrections finalisees ;
- commentaire ajoute sur un projet.

## Base De Donnees

Pour une nouvelle installation, importer `student-hub-php/database.sql`.

Pour une base existante, executer `migrate.sql`. Il ajoute ou verifie :

- `exercices.archive`
- `utilisateurs.first_login`
- `exercice_designated_chefs`
- `password_reset_requests`
- colonnes communautaires de `commentaires`
- table `notifications`
- nettoyage des notes globales qui affichaient des criteres a `0`

## Installation Locale

1. Placer le projet dans XAMPP :

```text
C:\xampp\htdocs\COMPO-FINAL\php-mvc-assignment-manager
```

2. Importer la base dans phpMyAdmin :

```text
student-hub-php/database.sql
```

3. Verifier la configuration :

```php
student-hub-php/config/config.php
```

4. Ouvrir l'application :

```text
http://localhost/COMPO-FINAL/php-mvc-assignment-manager/student-hub-php/public/
```

## Comptes De Test

Mot de passe par defaut : `password123`

- Admin : `admin@emsp.ci`
- Professeur : `amadou@emsp.ci`
- Etudiant : `jean.koffi@emsp.ci`

## Verification

Commandes conseillees apres modification :

```powershell
C:\xampp\php\php.exe -l student-hub-php\app\Controllers\CommunityController.php
C:\xampp\php\php.exe -l student-hub-php\app\Controllers\NotificationController.php
C:\xampp\php\php.exe -l student-hub-php\app\Controllers\ProfController.php
C:\xampp\php\php.exe -l student-hub-php\app\Controllers\StudentController.php
C:\xampp\php\php.exe -l student-hub-php\app\Controllers\AdminController.php
```

Verification effectuee le 02/06/2026 :

- `php -l` OK sur 46 fichiers PHP.
- `GET /`, `GET /community` et `GET /auth/login` OK en HTTP 200.
- Connexion admin OK puis pages `admin/dashboard`, `admin/exercises`, `admin/exercise/create`, `admin/students`, `admin/students/import` sans erreur technique ni warning visible.
- Connexion professeur OK puis `prof/dashboard` sans erreur technique ni warning visible.
- Connexion etudiant OK puis `student/dashboard`, `student/peer-gallery`, `community` sans erreur technique ni warning visible.
- Recherche Tailwind dans les vues PHP : aucune classe Tailwind restante detectee.
