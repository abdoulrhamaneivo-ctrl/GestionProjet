# État actuel du projet

## Fonctionnel
- Authentification admin / prof / étudiant
- CRUD exercices, étudiants, groupes, thèmes
- Import CSV étudiants + export TXT identifiants
- Soumission projet individuel ou groupe avec upload PDF (CDC)
- Notation professeur : mode critères (Design/5, Code/5, Fonctionnel/10) et mode global (/20)
- Publication des notes
- Export notes par exercice ou global
- Dérogations / extensions de délai pour étudiant ou groupe
- Verrouillage automatique à la date limite
- Section “Rendus manquants” sur dashboard prof avec notation manuelle
- Peer-gallery + commentaires pseudonymisés
- PDF bilan notes étudiants

## En cours / à finir
- Deadline auto-lock “avec confirmation professeur” : pour l’instant cliquable comme “Fin des corrections” ; reste à ajouter une logique métier séparée si besoin
- Page/formulaire de dérogation côté prof : déjà créée et fonctionnelle, reste validation UX
- Scalabilité SEO, accessibilité et tests unitaires

## Bugs corrigés
- Ternaire imbriqué invalide dans `ProfController::showGradeForm()` remplacé par `if/elseif`
- Accès incohérents à la session : `AuthController` stocke `id_user` ; tous les contrôleurs lisent maintenant `id_user`
- Warning `Undefined array key "id"` dans `StudentController` corrigé

## Convention importante
- Liens : toujours passer par le helper `url('route')`
- Erreurs : journaliser en serveur, ne jamais renvoyer `$e->getMessage()` à l’utilisateur
- Langue UI : français formel
