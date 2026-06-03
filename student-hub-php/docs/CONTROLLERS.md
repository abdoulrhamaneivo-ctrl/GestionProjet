# Contrôleurs

## AuthController
- `showLogin` / `processLogin` / `processLogout`

## AdminController
- `dashboard` : vue admin avec exercices/étudiants/profs
- `showCreateExercise` / `processCreateExercise`
- `showEditExercise` / `processUpdateExercise`
- `processDeleteExercise`
- `processToggleBlock`
- `listExercises` / `showManageGroups` / `processCreateGroup`
- `processAddExemption`
- `showImportStudents` / `processImportStudents` / `downloadImportCsv`
- `exportAllStudentsCsv`
- `listStudents` / `showCreateStudent` / `processCreateStudent`
- `showEditStudent` / `processUpdateStudent` / `processDeleteStudent`
- `exportExerciseGrades` / `exportAllExercisesGrades`

## ProfController
- `dashboard` : exercices du prof + rendus manquants + indicateur de retard
- `showGradeForm` : notation par `id_projet` ou par exercice + étudiant/groupe
- `processGrade` : inscription note (critères ou global)
- `confirmCorrectionsFinished`
- `showAddExemption` / `processAddExemption`
- `exportExerciseGrades` / `exportAllExercisesGrades`

## StudentController
- `dashboard` : statut des exercices, groupes, soumissions
- `showSubmitForm` / `processSubmit` : dépôt projet + upload PDF
- `peerGallery` / `addPeerComment`
- `downloadReportPdf`
