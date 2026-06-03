# Deploiement EMSP Assignment Manager

Ce guide prepare une installation propre de la plateforme avec une base vide et un seul administrateur.

## 1. Copier le projet

Placer le dossier dans le serveur web :

```text
C:\xampp\htdocs\COMPO-FINAL\php-mvc-assignment-manager
```

URL locale attendue :

```text
http://localhost/COMPO-FINAL/php-mvc-assignment-manager/student-hub-php/public/
```

## 2. Configurer la base

Verifier le fichier :

```text
student-hub-php/config/config.php
```

La base attendue est :

```text
emsp_assignment_db
```

## 3. Creer ou remettre la base a zero

Dans phpMyAdmin, importer d'abord :

```text
student-hub-php/database.sql
```

Ensuite, si vous voulez vider toutes les donnees et garder uniquement l'administrateur `abdoulivo.ci`, executer ce SQL :

```sql
USE `emsp_assignment_db`;

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `notifications`;
TRUNCATE TABLE `password_reset_requests`;
TRUNCATE TABLE `commentaires`;
TRUNCATE TABLE `projets`;
TRUNCATE TABLE `derogations`;
TRUNCATE TABLE `membres_groupe`;
TRUNCATE TABLE `groupes`;
TRUNCATE TABLE `exercice_designated_chefs`;
TRUNCATE TABLE `themes`;
TRUNCATE TABLE `exercices`;
TRUNCATE TABLE `import_identifiants`;
TRUNCATE TABLE `utilisateurs`;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO `utilisateurs`
(`id_user`, `nom`, `prenom`, `email`, `mot_de_passe`, `role`, `first_login`)
VALUES
(1, 'Ivo', 'Abdoul', 'abdoulivo.ci', '$2y$10$pXe/6v4UaJUT9EPSi68dp.dBkPyg5QiC0ofHHMq4f.HzZ6HPfV7zS', 'admin', 0);
```

Compte administrateur :

```text
Identifiant : abdoulivo.ci
Mot de passe : ivoabdoul123
```

## 4. Verifier les migrations

Pour une ancienne base deja existante, executer aussi :

```text
migrate.sql
```

Il verifie notamment :

- `exercices.archive`
- `utilisateurs.first_login`
- `exercice_designated_chefs`
- `password_reset_requests`
- colonnes communautaires de `commentaires`
- table `notifications`

## 5. Points fonctionnels a tester

1. Connexion admin avec `abdoulivo.ci`.
2. Creation d'un professeur.
3. Creation d'un exercice avec date future.
4. Import des etudiants.
5. Designation des chefs de groupe.
6. Connexion etudiant, creation groupe et soumission.
7. Connexion professeur, notation par criteres et notation globale.
8. Publication des notes de l'exercice.
9. Export des notes de l'exercice et export global.
10. Affichage communautaire, commentaires et reponses.

## 6. Commandes de verification PHP

Depuis la racine du projet :

```powershell
C:\xampp\php\php.exe -l student-hub-php\app\Controllers\ProfController.php
C:\xampp\php\php.exe -l student-hub-php\app\Controllers\CommunityController.php
C:\xampp\php\php.exe -l student-hub-php\app\Models\Comment.php
C:\xampp\php\php.exe -l student-hub-php\app\Views\community\index.php
C:\xampp\php\php.exe -l student-hub-php\app\Views\community\show.php
```

