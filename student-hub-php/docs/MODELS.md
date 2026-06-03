# Modèles

## Exercise
- `getAll()` : tous les exercices
- `getByProfesseur(int)` : exercices d’un professeur
- `findById(int)` : exercice par ID
- `create(string titre, string type, string dateLimite, ?int professeurId)`
- `addTheme(int exerciceId, string nomTheme)`
- `getThemes(int exerciceId)`
- `update(int id, array data)` : titre, type, date, blocage, professeur_id
- `updateBlockStatus(int id, int blocked)`
- `delete(int id)` : suppression en cascade
- `addDerogation(int exerciceId, ?int userId, ?int groupId, string nouvelleDate)`
- `checkSubmissionStatus(int exerciceId, int userId)` : statut soumission
- `markCorrectionsFinished(int exerciceId)`

## User
- `findAllByRole(string role)`
- `findById(int id)`
- `findByEmail(string email)`
- `findAvailableStudentsForExercise(int exerciceId)`
- `update(int id, array data)`
- `delete(int id)`
- `countByRole(string role)`

## Group
- `create(int exerciceId, int chefId, string nomGroupe)`
- `addMember(int groupId, int userId)`
- `removeMember(int groupId, int userId)`
- `findUserGroupForExercise(int exerciceId, int userId)`
- `getMembers(int groupId)`
- `getGroupsForExercise(int exerciceId)`
- `getMissingSubmissionsForExercise(int exerciceId)`

## Project
- `create(array data)`
- `update(int projectId, array data)`
- `findById(int id)`
- `findSubmittedProject(int exerciceId, int userId)`
- `grade(int projectId, float design, float code, float fonc, ?string critique)`
- `gradeGlobal(int projectId, float total, ?string critique)`
- `publishGradesForExercise(int exerciceId)`
- `getSubmissionsForExercise(int exerciceId)`
- `getUserGradesReport(int userId)`
- `setPublicStatus(int projectId, bool isPublic)`
- `getPublicProjectsForGallery()`

## Comment
- `create(int projectId, int userId, ?string pseudonyme, string contenu)`
- `getCommentsByProject(int projectId)`


