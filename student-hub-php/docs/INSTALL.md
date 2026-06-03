# Installation

## Prérequis
- PHP 8.1+ avec extensions `pdo_mysql`, `mbstring`, `fileinfo`
- MySQL/MariaDB
- Apache avec `mod_rewrite` activé
- XAMPP recommandé sous Windows

## Étapes
1. Placer le projet sous `htdocs`
2. Importer `student-hub-php/database.sql` dans phpMyAdmin
3. Vérifier `student-hub-php/config/config.php`
4. Accéder à `http://localhost/COMPO-FINAL/php-mvc-assignment-manager/student-hub-php/`

## Comptes par défaut
- Admin : `admin@emsp.ci` / `password123`
- Prof : `amadou@emsp.ci` / `password123`
- Étudiants : `jean.koffi@emsp.ci` / `password123`, etc.

## Uploads
- Dossier `student-hub-php/public/uploads/` doit être accessible en écriture

## Dépannage Windows
- Les chemins sont normalisés via `SCRIPT_NAME` (`public/index.php`, `app/Core/Controller.php`, `app/Core/Router.php`)
- Ne pas utiliser `$_SERVER['DOCUMENT_ROOT']`
