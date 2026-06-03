# Architecture du projet

- Stack : PHP 8+, MySQL/MariaDB, pas de framework (MVC maison)
- Front : Tailwind via CDN
- PDF : FPDF maison (`app/Core/PdfReport.php`)
- Point d'entrée : `public/index.php`
- Routes : `app/routes.php`
- Autoload : PSR-4 simple dans `public/index.php`

## Rôles applicatifs
- `etudiant` : soumission, peer-gallery, téléchargement PDF
- `prof` : notation, dérogations, export notes
- `admin` : CRUD exercices/étudiants/groupes, import CSV, blocages

## Sécurité
- CSRF sur tous les formulaires (`app/Core/Csrf.php`)
- `error_log()` pour les erreurs techniques, pas de `getMessage()` au front
- Helper global `url()` pour les liens (chemins relatifs interdits)
- `SCRIPT_NAME` utilisé pour la compatibilité Windows/sous-dossiers
