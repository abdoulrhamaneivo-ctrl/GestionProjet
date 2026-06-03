# Base de données

Script : `student-hub-php/database.sql`

## Tables principales
- `utilisateurs` : `id_user`, `nom`, `prenom`, `email`, `mot_de_passe` (bcrypt), `role` (`etudiant|prof|admin`)
- `exercices` : `id_exercice`, `titre`, `type_exercice` (`individuel|groupe`), `date_limite`, `est_bloque`, `corrections_terminees`, `professeur_id`
- `themes` : sujet/thématique par exercice
- `groupes` / `membres_groupe` : groupes de projets et membres
- `projets` : soumissions + notes (`note_design`, `note_code`, `note_fonc`, `note_totale`, `critique_prof`, `notes_publiees`, `statut_public`)
- `commentaires` : retours peer-testing
- `derogations` : extensions de délai (`id_user` ou `id_groupe`)
- `import_identifiants` : mots de passe en clair issus des imports CSV

## Points importants
- Hash bcrypt connu pour `password123` : `$2y$10$eYR4EUencFkb8nCtVncwfuOEpw1E5NcBI2CzLDSlGjHaxbKzxdRQq`
- `professeur_id` dans `exercices` avec `ON DELETE SET NULL`
- `import_identifiants` ne doit pas être exposée directement aux étudiants

## Données de départ (`database.sql`)
- Admin : `admin@emsp.ci`
- Prof : `amadou@emsp.ci`
- Étudiants : `jean.koffi@emsp.ci`, `fatou.traore@emsp.ci`, ...
- 2 exercices seedés, le premier avec `professeur_id = 2`
