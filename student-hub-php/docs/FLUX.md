# Flux métier

## Admin
1. Créer un exercice : titre, type, date limite, thèmes, professeur responsable
2. Gérer les groupes : créer équipes/binômes, affecter membres
3. Importer des étudiants par CSV : génération identifiants + export TXT
4. Accorder des dérogations / exemptions (admin)
5. Gérer blocages exercices
6. Exporter toutes les notes ou celles d’un exercice

## Professeur
1. Ouvrir son dashboard : exercices filtrés par `professeur_id`
2. Voir les rendus manquants
3. Noter manuellement un étudiant/groupe non soumis (placeholder créé)
4. Choisir le mode de notation :
   - critères : Design/5 + Code/5 + Fonctionnel/10
   - global : note unique sur 20
5. Accorder une dérogation à un étudiant ou groupe
6. Publier les notes
7. Marquer “Fin des corrections” / verrouiller l’exercice
8. Exporter les notes de ses exercices

## Étudiant
1. Voir ses exercices, statuts et groupes
2. Soumettre un projet (individuel ou groupe)
3. Mettre à jour sa soumission avant la date limite
4. Consulter l’espace peer-testing et commenter les projets publics
5. Télécharger son bilan PDF de notes

## Règles métier clés
- `corrections_terminees = 1` => plus aucune soumission possible
- `est_bloque = 1` => soumission bloquée
- `derogations` permet d’étendre la deadline ciblée étudiant/groupe
- `notes_publiees = 1` => notes visibles par l’étudiant
- `statut_public = 1` => projet visible dans la peer-gallery après date limite


