<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Models\User;
use App\Models\PasswordResetRequest;
use PDO;

final class AdminController extends Controller
{
    // MD-ARCHIVE: dashboard/list/archive-toggle admin (getActive/getArchived/toggleArchive)
    // MD-CHANGEPWD: import/création étudiants & professeurs : first_login=1, import_identifiants créé puis supprimé après changement
    public function dashboard(): void
    {
        $this->authorize(['admin']);

        $exercises = \App\Models\Exercise::getAll();
        $students = User::findAllByRole('etudiant');
        $professors = User::findAllByRole('prof');

        $stats = [
            'total_exercises' => count($exercises),
            'total_students' => count($students),
            'total_professors' => count($professors),
            'active_exercises' => 0,
            'archived_exercises' => 0,
            'total_submissions' => 0,
            'total_comments' => 0,
            'total_groups' => 0,
            'pending_corrections' => 0
        ];

        foreach ($exercises as $ex) {
            if (!empty($ex['archive'])) {
                $stats['archived_exercises']++;
                continue;
            }
            $stats['active_exercises']++;
            
            $subs = \App\Models\Project::getSubmissionsForExercise((int) $ex['id_exercice']);
            $stats['total_submissions'] += count($subs);
            
            if (empty($ex['corrections_terminees'])) {
                $stats['pending_corrections']++;
            }
        }

        $activeExercises = \App\Models\Exercise::getActive();
        $archivedExercises = \App\Models\Exercise::getArchived();

        $stats['total_groups'] = \App\Models\Group::countTotalForExercise(0);

        $activeExercises = array_values(array_filter($exercises, static fn ($ex) => empty($ex['archive'])));
        $archivedExercises = array_values(array_filter($exercises, static fn ($ex) => !empty($ex['archive'])));
        $exerciseStats = [];
        foreach ($exercises as $exercise) {
            $exerciseStats[(int) $exercise['id_exercice']] = $this->buildExerciseStats($exercise, count($students));
        }

        $this->render('admin/dashboard', [
            'user' => Session::get('user'),
            'exercises' => $activeExercises,
            'active_exercises' => $activeExercises,
            'archived_exercises' => $archivedExercises,
            'students' => $students,
            'professors' => $professors,
            'stats' => $stats,
            'exerciseStats' => $exerciseStats,
            'passwordRequests' => PasswordResetRequest::getPending(),
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
            'extraScripts' => [
                ['src' => 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js']
            ]
        ], 'Administration - EMSP Hub');
    }

    public function showCreateExercise(): void
    {
        $this->authorize(['admin']);

        $this->render('admin/exercise_create', [
            'user' => Session::get('user'),
            'csrf_token' => Csrf::generateToken(),
            'professors' => User::findAllByRole('prof'),
            'students' => User::findAllByRole('etudiant'),
            'error' => Session::getFlash('error')
        ], 'Publier un Nouvel Exercice');
    }

    public function processCreateExercise(): void
    {
        $this->authorize(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/dashboard', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('admin/dashboard', null, 'Token CSRF invalide.');
        }

        $titre = trim((string) ($_POST['titre'] ?? ''));
        $type = trim((string) ($_POST['type_exercice'] ?? 'individuel'));
        $deadline = trim((string) ($_POST['date_limite'] ?? ''));
        $rawThemes = trim((string) ($_POST['themes_list'] ?? ''));
        $professeurId = !empty($_POST['professeur_id']) ? (int) $_POST['professeur_id'] : null;
        $chefIds = [];
        if (!empty($_POST['chef_ids']) && is_array($_POST['chef_ids'])) {
            foreach ($_POST['chef_ids'] as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $chefIds[] = $id;
                }
            }
            $chefIds = array_values(array_unique($chefIds));
        }

        $createNewProf = !empty($_POST['create_new_prof']);
        $newProfNom = trim((string) ($_POST['new_prof_nom'] ?? ''));
        $newProfPrenom = trim((string) ($_POST['new_prof_prenom'] ?? ''));
        $newProfEmail = trim((string) ($_POST['new_prof_email'] ?? ''));

        if ($titre === '' || $deadline === '') {
            $this->redirect('admin/exercise/create', null, 'Veuillez renseigner le titre et la date limite.');
        }

        if (!$this->isFutureDateTime($deadline)) {
            $this->redirect('admin/exercise/create', null, 'La date limite doit etre une date future valide.');
        }

        try {
            $generatedPasswordForView = null;

            if ($createNewProf && $newProfNom !== '' && $newProfPrenom !== '' && $newProfEmail !== '') {
                // MD-CHANGEPWD: compte prof créé ici : first_login=1 via User::create(), mot de passe stocké en clair dans import_identifiants
                $generatedPassword = $this->generateSecurePassword(10);
                $hashedPassword = password_hash($generatedPassword, PASSWORD_DEFAULT);

                $professeurId = User::create($newProfNom, $newProfPrenom, $newProfEmail, $hashedPassword, 'prof');

                $db = Database::pdo();
                $stmt = $db->prepare('INSERT INTO import_identifiants (id_user, mot_de_passe_clair) VALUES (?, ?)');
                $stmt->execute([$professeurId, $generatedPassword]);

                $generatedPasswordForView = $generatedPassword;
            }

            $exId = \App\Models\Exercise::create($titre, $type, $deadline, $professeurId);

            if ($rawThemes !== '') {
                $themesArray = explode(',', $rawThemes);
                foreach ($themesArray as $themeName) {
                    $themeName = trim($themeName);
                    if ($themeName !== '') {
                        \App\Models\Exercise::addTheme($exId, $themeName);
                    }
                }
            }

            if ($type === 'groupe' && !empty($chefIds)) {
                \App\Models\Exercise::replaceDesignatedChefs($exId, $chefIds);
            }

            if ($professeurId !== null) {
                \App\Models\Notification::create(
                    (int) $professeurId,
                    'exercice',
                    'Nouvel exercice assigne',
                    'Vous etes responsable de "' . $titre . '".',
                    'prof/dashboard'
                );
            }
            \App\Models\Notification::createForUsers(
                $chefIds,
                'exercice',
                'Vous etes chef de groupe',
                'Vous pouvez constituer votre groupe pour "' . $titre . '".',
                'student/dashboard'
            );

            $successMsg = 'L\'exercice "' . $titre . '" a ete publie avec succes.';
            if ($generatedPasswordForView !== null) {
                \App\Core\Session::set('generated_prof_password', $generatedPasswordForView);
                \App\Core\Session::set('generated_prof_email', $newProfEmail);
                \App\Core\Session::set('reset_password_result', [
                    'email' => $newProfEmail,
                    'password' => $generatedPasswordForView,
                ]);
                $successMsg .= ' Compte professeur cree : ' . $newProfEmail . '. Le mot de passe est affiche dans la fenetre de confirmation.';
            }

            error_log('[processCreateExercise] exId=' . $exId . ' titre=' . $titre . ' profId=' . $professeurId);

            $this->redirect('admin/dashboard', $successMsg);
        } catch (\Throwable $e) {
            error_log('[AdminController::processCreateExercise] ' . $e->getMessage());
            $this->redirect('admin/exercise/create', null, 'Une erreur est survenue lors de la création de l\'exercice.');
        }
    }

    public function processToggleBlock(): void
    {
        $this->authorize(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/dashboard', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('admin/dashboard', null, 'Token CSRF invalide.');
        }

        $exId = (int) ($_POST['id_exercice'] ?? 0);
        $currentBlocked = (int) ($_POST['est_bloque'] ?? 0);
        $newBlocked = $currentBlocked === 1 ? 0 : 1;

        try {
            \App\Models\Exercise::updateBlockStatus($exId, $newBlocked);
            $statusText = $newBlocked === 1 ? 'verrouille' : 'deverrouille';
            $this->redirect('admin/dashboard', 'L\'exercice a ete ' . $statusText . ' avec succes.');
        } catch (\Throwable $e) {
            error_log('[AdminController::processToggleBlock] ' . $e->getMessage());
            $this->redirect('admin/dashboard', null, 'Erreur lors de la modification du statut de l\'exercice.');
        }
    }

    public function listExercises(): void
    {
        $this->authorize(['admin']);

        $search = trim((string) ($_GET['search'] ?? ''));
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $perPage = 25;

        $allExercises = \App\Models\Exercise::getAll($search !== '' ? $search : null);
        $total = \App\Models\Exercise::countAll($search !== '' ? $search : null);
        $totalPages = (int) ceil($total / $perPage);
        $page = min(max(1, $page), max(1, $totalPages));

        $active = array_values(array_filter($allExercises, static fn ($ex) => empty($ex['archive'])));
        $archived = array_values(array_filter($allExercises, static fn ($ex) => !empty($ex['archive'])));

        $this->render('admin/exercises_list', [
            'user' => Session::get('user'),
            'active_exercises' => $active,
            'archived_exercises' => $archived,
            'csrf_token' => Csrf::generateToken(),
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
            'search' => $search,
            'page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
            'per_page' => $perPage
        ], 'Gestion des Exercices');
    }

    public function showEditExercise(): void
    {
        $this->authorize(['admin']);

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->redirect('admin/exercises', null, 'ID exercice invalide.');
        }

        $exercise = \App\Models\Exercise::findById($id);
        if (!$exercise) {
            $this->redirect('admin/exercises', null, 'Exercice introuvable.');
        }

        $this->render('admin/exercise_form', [
            'user' => Session::get('user'),
            'csrf_token' => Csrf::generateToken(),
            'exercise' => $exercise,
            'themes' => \App\Models\Exercise::getThemes($id),
            'professors' => User::findAllByRole('prof'),
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error')
        ], 'Modifier l\'Exercice');
    }

    public function processUpdateExercise(): void
    {
        $this->authorize(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/exercises', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('admin/exercises', null, 'Token CSRF invalide.');
        }

        $id = (int) ($_POST['id_exercice'] ?? 0);
        if ($id <= 0) {
            $this->redirect('admin/exercises', null, 'ID exercice invalide.');
        }

        $exercise = \App\Models\Exercise::findById($id);
        if (!$exercise) {
            $this->redirect('admin/exercises', null, 'Exercice introuvable.');
        }

        $titre = trim((string) ($_POST['titre'] ?? ''));
        $type = trim((string) ($_POST['type_exercice'] ?? 'individuel'));
        $deadline = trim((string) ($_POST['date_limite'] ?? ''));
        $estBloque = isset($_POST['est_bloque']) ? 1 : 0;
        $professeurId = !empty($_POST['professeur_id']) ? (int) $_POST['professeur_id'] : null;

        if ($titre === '' || $deadline === '') {
            $this->redirect('admin/exercises/edit?id=' . $id, null, 'Titre et date limite sont obligatoires.');
        }

        if (!$this->isFutureDateTime($deadline)) {
            $this->redirect('admin/exercises/edit?id=' . $id, null, 'La date limite doit etre une date future valide.');
        }

        try {
            \App\Models\Exercise::update($id, [
                'titre' => $titre,
                'type_exercice' => $type,
                'date_limite' => $deadline,
                'est_bloque' => $estBloque,
                'professeur_id' => $professeurId
            ]);

            $this->redirect('admin/exercises', 'Exercice modifie avec succes.');
        } catch (\Throwable $e) {
            error_log('[AdminController::processUpdateExercise] ' . $e->getMessage());
            $this->redirect('admin/exercises/edit?id=' . $id, null, 'Erreur lors de la modification de l\'exercice.');
        }
    }

    public function processDeleteExercise(): void
    {
        $this->authorize(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/exercises', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('admin/exercises', null, 'Token CSRF invalide.');
        }

        $id = (int) ($_POST['id_exercice'] ?? 0);
        if ($id <= 0) {
            $this->redirect('admin/exercises', null, 'ID exercice invalide.');
        }

        $exercise = \App\Models\Exercise::findById($id);
        if (!$exercise) {
            $this->redirect('admin/exercises', null, 'Exercice introuvable.');
        }

        try {
            \App\Models\Exercise::delete($id);
            $this->redirect('admin/exercises', 'Exercice supprime avec succes.');
        } catch (\Throwable $e) {
            error_log('[AdminController::processDeleteExercise] ' . $e->getMessage());
            $this->redirect('admin/exercises', null, 'Erreur lors de la suppression de l\'exercice.');
        }
    }

    public function processToggleArchive(): void
    {
        $this->authorize(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/dashboard', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('admin/dashboard', null, 'Token CSRF invalide.');
        }

        $id = (int) ($_POST['id_exercice'] ?? 0);
        $exercise = \App\Models\Exercise::findById($id);

        if (!$exercise) {
            $this->redirect('admin/exercises', null, 'Exercice introuvable.');
        }

        try {
            \App\Models\Exercise::toggleArchive($id);
            $this->redirect('admin/exercises', 'Statut d\'archivage mis a jour avec succes.');
        } catch (\Throwable $e) {
            error_log('[AdminController::processToggleArchive] ' . $e->getMessage());
            $this->redirect('admin/exercises', null, 'Erreur lors de la modification du statut d\'archivage.');
        }
    }

    public function dismissResetPasswordResult(): void
    {
        $this->authorize(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/dashboard');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('admin/dashboard', null, 'Token CSRF invalide.');
        }

        \App\Core\Session::remove('reset_password_result');
        $this->redirect('admin/dashboard');
    }

    public function resolvePasswordReset(): void
    {
        $admin = $this->authorize(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/dashboard', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('admin/dashboard', null, 'Token CSRF invalide.');
        }

        $requestId = (int) ($_POST['id_request'] ?? 0);
        $request = PasswordResetRequest::findPendingById($requestId);
        if ($request === null) {
            $this->redirect('admin/dashboard', null, 'Demande introuvable ou deja traitee.');
        }

        try {
            $generated = $this->generateSecurePassword(10);
            $hashed = password_hash($generated, PASSWORD_DEFAULT);
            $db = Database::pdo();
            $db->prepare('UPDATE utilisateurs SET mot_de_passe = ?, first_login = 1 WHERE id_user = ?')
                ->execute([$hashed, (int) $request['id_user']]);
            $db->prepare('INSERT INTO import_identifiants (id_user, mot_de_passe_clair) VALUES (?, ?)')
                ->execute([(int) $request['id_user'], $generated]);
            PasswordResetRequest::markResolved($requestId, (int) $admin['id_user'], $generated);

            Session::set('reset_password_result', [
                'email' => $request['email'],
                'password' => $generated
            ]);
            $this->redirect('admin/dashboard', 'Mot de passe reinitialise.');
        } catch (\Throwable $e) {
            error_log('[AdminController::resolvePasswordReset] ' . $e->getMessage());
            $this->redirect('admin/dashboard', null, 'Erreur lors de la reinitialisation.');
        }
    }

    public function showManageGroups(): void
    {
        $this->authorize(['admin']);

        $exId = (int) ($_GET['id'] ?? 0);
        $exercice = \App\Models\Exercise::findById($exId);

        if ($exercice === null) {
            $this->redirect('admin/dashboard', null, 'Exercice introuvable.');
        }

        $students = User::findAllByRole('etudiant');
        $groups = \App\Models\Group::getGroupsForExercise($exId);

        $designatedChefs = [];
        $designatedChefIds = [];
        if ($exercice['type_exercice'] === 'groupe') {
            $db = Database::pdo();
            $stmt = $db->prepare('
                SELECT u.id_user, u.nom, u.prenom, u.email 
                FROM utilisateurs u
                JOIN exercice_designated_chefs edc ON u.id_user = edc.id_user
                WHERE edc.id_exercice = ?
                ORDER BY u.nom ASC, u.prenom ASC
            ');
            $stmt->execute([$exId]);
            $designatedChefs = $stmt->fetchAll();
            foreach ($designatedChefs as $dc) {
                $designatedChefIds[(int) $dc['id_user']] = true;
            }
        }

        $busyUserIds = [];
        foreach ($groups as $grp) {
            $chefId = (int) ($grp['id_chef'] ?? 0);
            if ($chefId > 0) {
                $busyUserIds[$chefId] = true;
            }
            $members = \App\Models\Group::getMembers((int) $grp['id_groupe']);
            foreach ($members as $member) {
                $busyUserIds[(int) $member['id_user']] = true;
            }
        }

        $groupsDetailed = [];
        foreach ($groups as $grp) {
            $groupsDetailed[] = [
                'group' => $grp,
                'members' => \App\Models\Group::getMembers((int) $grp['id_groupe'])
            ];
        }

        $derogations = \App\Models\Exercise::getDerogationsForExercise($exId);

        $this->render('admin/group_manage', [
            'user' => Session::get('user'),
            'exercice' => $exercice,
            'students' => $students,
            'designatedChefs' => $designatedChefs,
            'designatedChefIds' => $designatedChefIds,
            'busyUserIds' => $busyUserIds,
            'groups' => $groupsDetailed,
            'derogations' => $derogations,
            'csrf_token' => Csrf::generateToken(),
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error')
        ], 'Gerer Groupes : ' . $exercice['titre']);
    }

    public function processCreateGroup(): void
    {
        $this->authorize(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/dashboard', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('admin/dashboard', null, 'Token CSRF invalide.');
        }

        $exId = (int) ($_POST['id_exercice'] ?? 0);
        $chefId = (int) ($_POST['id_chef'] ?? 0);
        $nomGroupe = trim((string) ($_POST['nom_groupe'] ?? ''));

        if ($chefId === 0 || $nomGroupe === '') {
            $this->redirect('admin/group/manage?id=' . $exId, null, 'Le chef de groupe et le nom du groupe sont requis.');
        }

        try {
            $db = Database::pdo();
            $chefExists = Database::pdo()->prepare('
                SELECT 1 FROM groupes g
                WHERE g.id_exercice = ?
                  AND (g.id_chef = ?
                       OR EXISTS (
                           SELECT 1 FROM membres_groupe mg
                           WHERE mg.id_groupe = g.id_groupe
                             AND mg.id_user = ?
                       ))
                LIMIT 1
            ');
            $chefExists->execute([$exId, $chefId, $chefId]);
            if ((bool) $chefExists->fetchColumn()) {
                $this->redirect('admin/group/manage?id=' . $exId, null, 'Le chef choisi est déjà dans un groupe pour cet exercice.');
            }

            $groupId = \App\Models\Group::create($exId, $chefId, $nomGroupe);

            $members = $_POST['members'] ?? [];
            $ins = $db->prepare('INSERT IGNORE INTO membres_groupe (id_groupe, id_user) VALUES (?, ?)');
            foreach ($members as $memId) {
                $memId = (int) $memId;
                if ($memId === $chefId) {
                    continue;
                }
                
                // Student cannot be in multiple groups for the same exercise (chef or member)
                if (\App\Models\Group::findUserGroupForExercise($exId, $memId) !== null) {
                    $this->redirect('admin/group/manage?id=' . $exId, null, htmlspecialchars('Un étudiant choisi est déjà dans un groupe pour cet exercice.', ENT_QUOTES, 'UTF-8'));
                }

                // Designated chef cannot be added as member of another group
                if (\App\Models\Exercise::isDesignatedChef($exId, $memId)) {
                    $this->redirect('admin/group/manage?id=' . $exId, null, htmlspecialchars('Un chef de groupe désigné ne peut pas être choisi comme membre.', ENT_QUOTES, 'UTF-8'));
                }

                $ins->execute([$groupId, $memId]);
            }

            $this->redirect('admin/group/manage?id=' . $exId, 'Groupe "' . $nomGroupe . '" cree avec succes.');
        } catch (\Throwable $e) {
            error_log('[AdminController::processCreateGroup] ' . $e->getMessage());
            $this->redirect('admin/group/manage?id=' . $exId, null, 'Erreur lors de la création du groupe.');
        }
    }

    public function processAddExemption(): void
    {
        $this->authorize(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/dashboard', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('admin/dashboard', null, 'Token CSRF invalide.');
        }

        $exId = (int) ($_POST['id_exercice'] ?? 0);
        $targetUserId = (int) ($_POST['id_user_target'] ?? 0);
        $targetGroupId = (int) ($_POST['id_group_target'] ?? 0);
        $newValue = trim((string) ($_POST['nouvelle_date'] ?? ''));

        if ($newValue === '') {
            $this->redirect('admin/group/manage?id=' . $exId, null, 'Veuillez choisir une date de derogation.');
        }

        if (!$this->isFutureDateTime($newValue)) {
            $this->redirect('admin/group/manage?id=' . $exId, null, 'La date de derogation doit etre une date future valide.');
        }

        try {
            $targetUser = $targetUserId > 0 ? $targetUserId : null;
            $targetGroup = $targetGroupId > 0 ? $targetGroupId : null;

            \App\Models\Exercise::addDerogation($exId, $targetUser, $targetGroup, $newValue);
            $exercise = \App\Models\Exercise::findById($exId);
            $this->notifyDerogationTarget(
                $targetUser,
                $targetGroup,
                'Derogation accordee',
                'Une nouvelle date limite a ete accordee pour "' . ($exercise['titre'] ?? 'un exercice') . '".'
            );

            $this->redirect('admin/group/manage?id=' . $exId, 'Extension de delai accordee.');
        } catch (\Throwable $e) {
            error_log('[AdminController::processAddExemption] ' . $e->getMessage());
            $this->redirect('admin/group/manage?id=' . $exId, null, 'Erreur lors de l\'enregistrement de la dérogation.');
        }
    }

    public function processDeleteExemption(): void
    {
        $this->authorize(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/dashboard', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('admin/dashboard', null, 'Token CSRF invalide.');
        }

        $exId = (int) ($_POST['id_exercice'] ?? 0);
        $derogationId = (int) ($_POST['id_derogation'] ?? 0);

        try {
            \App\Models\Exercise::deleteDerogation($derogationId);
            $this->redirect('admin/group/manage?id=' . $exId, 'Derogation supprimee avec succes.');
        } catch (\Throwable $e) {
            error_log('[AdminController::processDeleteExemption] ' . $e->getMessage());
            $this->redirect('admin/group/manage?id=' . $exId, null, 'Erreur lors de la suppression de la derogation.');
        }
    }

    public function showImportStudents(): void
    {
        $this->authorize(['admin']);

        $importResult = Session::get('import_result');

        $this->render('admin/import_students', [
            'user' => Session::get('user'),
            'csrf_token' => Csrf::generateToken(),
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
            'importResult' => $importResult
        ], 'Importer les etudiants');
    }

    public function processImportStudents(): void
    {
        $this->authorize(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/dashboard', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('admin/dashboard', null, 'Token CSRF invalide.');
        }

        if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $this->redirect('admin/students/import', null, 'Veuillez fournir un fichier TXT valide.');
        }

        $file = $_FILES['csv_file'];
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);

            if (stripos($mime, 'csv') === false && stripos($mime, 'text') === false && stripos($mime, 'octet') === false) {
                $this->redirect('admin/students/import', null, 'Le fichier doit etre au format TXT.');
            }

        $handle = fopen($file['tmp_name'], 'rb');
        if ($handle === false) {
            $this->redirect('admin/students/import', null, 'Impossible de lire le fichier envoye.');
        }

        $db = Database::pdo();
        $db->beginTransaction();

        $inserted = 0;
        $skipped = 0;
        $errors = [];
        $insertedStudents = [];

        try {
            $checkStmt = $db->prepare('SELECT id_user FROM utilisateurs WHERE email = ? LIMIT 1');
            $insertStmt = $db->prepare('INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, first_login) VALUES (?, ?, ?, ?, ?, ?)');

            $rowNum = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                $fullName = trim((string) ($row[0] ?? ''));

                if ($fullName === '') {
                    $skipped++;
                    $errors[] = 'Ligne ' . $rowNum . ' ignoree (vide).';
                    continue;
                }

                $parts = preg_split('/\s+/', $fullName);
                $parts = array_values(array_filter($parts, static fn ($v) => $v !== ''));

                    if (count($parts) < 2) {
                        $skipped++;
                        $errors[] = 'Ligne ' . $rowNum . ' ignoree (nom complet attendu : Prenom Nom).';
                        continue;
                    }

                    // Format attendu dans le CSV : "Prenom Nom" (prénom d'abord, nom en dernier)
                    $nom = $parts[count($parts) - 1];
                    $prenomParts = array_slice($parts, 0, count($parts) - 1);
                    $prenom = implode(' ', $prenomParts);
                    $prenomComplet = $prenom;
                    $prenomSlug = strtolower(str_replace([' ', '-'], '', $prenom));
                    $nomSlug = strtolower($nom);
                    $motDePasse = $prenomSlug . $nomSlug . '123';
                    $email = strtolower($prenomSlug . '.' . $nomSlug . '@emsp.ci');

                $checkStmt->execute([$email]);
                if ($checkStmt->fetch()) {
                    $skipped++;
                    $errors[] = 'Ligne ' . $rowNum . ' : ' . $prenom . ' ' . $nom . ' (doublon).';
                    continue;
                }

                $hashed = password_hash($motDePasse, PASSWORD_DEFAULT);
                // MD-CHANGEPWD: import étudiant ici : first_login=1, mot de passe en clair conservé temporairement dans import_identifiants
                $insertStmt->execute([$nom, $prenomComplet, $email, $hashed, 'etudiant', 1]);

                $userId = (int) $db->lastInsertId();
                $idImportStmt = $db->prepare('INSERT INTO import_identifiants (id_user, mot_de_passe_clair) VALUES (?, ?)');
                $idImportStmt->execute([$userId, $motDePasse]);

                $insertedStudents[] = [
                    'nom' => $nom,
                    'prenom' => $prenomComplet,
                    'email' => $email,
                    'mot_de_passe' => $motDePasse,
                    'role' => 'etudiant'
                ];
                $inserted++;
            }

            fclose($handle);

            $db->commit();
            Session::set('import_result', [
                'students' => $insertedStudents,
                'inserted' => $inserted,
                'skipped' => $skipped,
                'errors' => $errors
            ]);
            $msg = $inserted . ' etudiant(s) importe(s).';

            if ($skipped > 0) {
                $msg .= ' ' . $skipped . ' ignor(s).';
            }

            $this->redirect('admin/students/import', $msg);
        } catch (\Throwable $e) {
            $db->rollBack();
            if (is_resource($handle) || $handle instanceof \SplFileObject) {
                fclose($handle);
            }

            error_log('[AdminController::processImportStudents] ' . $e->getMessage());
            $this->redirect('admin/students/import', null, 'Erreur lors de l\'importation du TXT.');
        }
    }

    public function downloadImportCsv(): void
    {
        $this->authorize(['admin']);

        $filename = 'etudiants_import_' . date('Y-m-d_H-i') . '.txt';

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $db = Database::pdo();
        // N'afficher que les comptes qui n'ont pas encore changé leur mot de passe (first_login=1)
        $stmt = $db->query('
            SELECT u.nom, u.prenom, u.email, i.mot_de_passe_clair
            FROM import_identifiants i
            JOIN utilisateurs u ON u.id_user = i.id_user
            WHERE u.first_login = 1
            ORDER BY i.date_import DESC, u.email ASC
        ');
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($users)) {
            echo "Aucun import disponible.";
            exit;
        }

        $lines = ['Identifiants temporaires (comptes non encore activés)', str_repeat('-', 50), ''];
        foreach ($users as $stu) {
            $lines[] = 'Nom         : ' . $stu['nom'] . ' ' . $stu['prenom'];
            $lines[] = 'Email       : ' . $stu['email'];
            $lines[] = 'Mot de passe : ' . $stu['mot_de_passe_clair'];
            $lines[] = str_repeat('-', 50);
        }

        echo implode(PHP_EOL, $lines);
        exit;
    }

    public function exportAllStudentsCsv(): void
    {
        $this->authorize(['admin']);

        $filename = 'etudiants_tous_' . date('Y-m-d_H-i') . '.txt';

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $db = Database::pdo();
        // Export des identifiants : mdp visible seulement pour comptes non encore activés (first_login=1)
        // Les comptes déjà activés affichent 'Deja modifie' -> pas de fuite du vrai mot de passe
        $stmt = $db->query("
            SELECT u.nom, u.prenom, u.email, u.role,
                   CASE WHEN u.first_login = 1
                        THEN COALESCE(i.mot_de_passe_clair, 'A distribuer')
                        ELSE 'Deja modifie'
                   END AS mot_de_passe_export
            FROM utilisateurs u
            LEFT JOIN import_identifiants i ON i.id_user = u.id_user
            ORDER BY u.nom ASC, u.prenom ASC
        ");
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $headers = ['Nom', 'Prenom', 'Email', 'Role', 'Mot de passe temporaire'];
        $rows = [];
        foreach ($users as $stu) {
            $rows[] = [
                $stu['nom'],
                $stu['prenom'],
                $stu['email'],
                $stu['role'],
                $stu['mot_de_passe_export']
            ];
        }

        $widths = array_map('strlen', $headers);
        foreach ($rows as $row) {
            foreach ($row as $index => $value) {
                $widths[$index] = max($widths[$index], strlen((string) $value));
            }
        }

        $line = '+';
        foreach ($widths as $width) {
            $line .= str_repeat('-', $width + 2) . '+';
        }

        $formatRow = static function (array $row) use ($widths): string {
            $cells = [];
            foreach ($row as $index => $value) {
                $cells[] = ' ' . str_pad((string) $value, $widths[$index]) . ' ';
            }
            return '|' . implode('|', $cells) . '|';
        };

        echo 'EXPORT TXT DES UTILISATEURS' . PHP_EOL;
        echo 'Genere le : ' . date('d/m/Y H:i') . PHP_EOL . PHP_EOL;
        echo $line . PHP_EOL;
        echo $formatRow($headers) . PHP_EOL;
        echo $line . PHP_EOL;
        foreach ($rows as $row) {
            echo $formatRow($row) . PHP_EOL;
        }
        echo $line . PHP_EOL;
        exit;
    }

    public function listStudents(): void
    {
        $this->authorize(['admin']);

        $search = trim((string) ($_GET['search'] ?? ''));
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $perPage = 25;

        $students = User::findAllByRole('etudiant', $search !== '' ? $search : null, $page, $perPage);
        $total = User::countByRole('etudiant', $search !== '' ? $search : null);
        $totalPages = (int) ceil($total / $perPage);
        $page = min(max(1, $page), max(1, $totalPages));

        $this->render('admin/students_list', [
            'user' => Session::get('user'),
            'students' => $students,
            'exercises' => \App\Models\Exercise::getAll(null, 1, 1000),
            'csrf_token' => Csrf::generateToken(),
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
            'search' => $search,
            'page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
            'per_page' => $perPage
        ], 'Gestion des etudiants');
    }

    public function showCreateStudent(): void
    {
        $this->authorize(['admin']);

        $this->render('admin/student_form', [
            'user' => Session::get('user'),
            'csrf_token' => Csrf::generateToken(),
            'student' => null,
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error')
        ], 'Creer un etudiant');
    }

    public function processCreateStudent(): void
    {
        $this->authorize(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/students', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('admin/students', null, 'Token CSRF invalide.');
        }

        $nom = trim((string) ($_POST['nom'] ?? ''));
        $prenom = trim((string) ($_POST['prenom'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));

        if ($nom === '' || $prenom === '' || $email === '') {
            $this->redirect('admin/students/create', null, 'Nom, prenom et email sont obligatoires.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect('admin/students/create', null, 'Email invalide.');
        }

        $existingUser = User::findByEmail($email);
        if ($existingUser) {
            $this->redirect('admin/students/create', null, 'Cet email existe deja.');
        }

        // MD-CHANGEPWD: création manuelle étudiant ici : first_login=1, mdp temporaire généré de façon sécurisée
        $generatedPassword = $this->generateSecurePassword(10);
        $motDePasse = password_hash($generatedPassword, PASSWORD_DEFAULT);
        $db = Database::pdo();
        $stmtIns = $db->prepare('INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, first_login) VALUES (?, ?, ?, ?, ?, ?)');
        $stmtIns->execute([$nom, $prenom, $email, $motDePasse, 'etudiant', 1]);
        $newUserId = (int) $db->lastInsertId();
        $db->prepare('INSERT INTO import_identifiants (id_user, mot_de_passe_clair) VALUES (?, ?)')->execute([$newUserId, $generatedPassword]);
        \App\Core\Session::set('reset_password_result', ['email' => $email, 'password' => $generatedPassword]);

        $this->redirect('admin/students', 'Etudiant cree avec succes.');
    }

    public function showEditStudent(): void
    {
        $this->authorize(['admin']);

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->redirect('admin/students', null, 'ID etudiant invalide.');
        }

        $student = User::findById($id);
        if (!$student) {
            $this->redirect('admin/students', null, 'Etudiant introuvable.');
        }

        $this->render('admin/student_form', [
            'user' => Session::get('user'),
            'csrf_token' => Csrf::generateToken(),
            'student' => $student,
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error')
        ], 'Modifier un etudiant');
    }

    public function processUpdateStudent(): void
    {
        $this->authorize(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/students', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('admin/students', null, 'Token CSRF invalide.');
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->redirect('admin/students', null, 'ID etudiant invalide.');
        }

        $nom = trim((string) ($_POST['nom'] ?? ''));
        $prenom = trim((string) ($_POST['prenom'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $role = trim((string) ($_POST['role'] ?? 'etudiant'));

        if ($nom === '' || $prenom === '' || $email === '') {
            $this->redirect('admin/students/edit?id=' . $id, null, 'Nom, prenom et email sont obligatoires.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect('admin/students/edit?id=' . $id, null, 'Email invalide.');
        }

        $student = User::findById($id);
        if (!$student) {
            $this->redirect('admin/students', null, 'Etudiant introuvable.');
        }

        $existingUser = User::findByEmail($email);
        if ($existingUser && (int) $existingUser['id_user'] !== $id) {
            $this->redirect('admin/students/edit?id=' . $id, null, 'Cet email est deja utilise par un autre compte.');
        }

        User::update($id, [
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'role' => $role
        ]);

        $this->redirect('admin/students', 'Etudiant modifie avec succes.');
    }

    public function listProfessors(): void
    {
        $this->authorize(['admin']);

        $search = trim((string) ($_GET['search'] ?? ''));
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $perPage = 25;

        $professors = User::findAllByRole('prof', $search !== '' ? $search : null, $page, $perPage);
        $total = User::countByRole('prof', $search !== '' ? $search : null);
        $totalPages = (int) ceil($total / $perPage);
        $page = min(max(1, $page), max(1, $totalPages));

        // Récupérer les mots de passe en clair en UNE SEULE requête (évite N+1)
        $profPasswords = [];
        if (!empty($professors)) {
            $profIds = array_map(static fn ($p) => (int) $p['id_user'], $professors);
            $placeholders = implode(',', array_fill(0, count($profIds), '?'));
            $db = Database::pdo();
            $stmt = $db->prepare(
                'SELECT id_user, mot_de_passe_clair FROM import_identifiants
                 WHERE id_user IN (' . $placeholders . ')
                 ORDER BY date_import DESC'
            );
            $stmt->execute($profIds);
            foreach ($stmt->fetchAll() as $row) {
                $uid = (int) $row['id_user'];
                // Garder seulement le plus récent (ORDER BY date_import DESC)
                if (!isset($profPasswords[$uid])) {
                    $profPasswords[$uid] = $row['mot_de_passe_clair'];
                }
            }
        }

        // Injecter le mot de passe temporaire dans chaque prof
        foreach ($professors as &$prof) {
            $uid = (int) $prof['id_user'];
            $prof['mot_de_passe_temporaire'] = $profPasswords[$uid] ?? 'Non disponible';
        }
        unset($prof);

        $this->render('admin/professors_list', [
            'user' => Session::get('user'),
            'professors' => $professors,
            'csrf_token' => Csrf::generateToken(),
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
            'search' => $search,
            'page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
            'per_page' => $perPage
        ], 'Gestion des Professeurs');
    }

    public function listAdmins(): void
    {
        $this->authorize(['admin']);

        $search = trim((string) ($_GET['search'] ?? ''));
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $perPage = 25;

        $admins = User::findAllByRole('admin', $search !== '' ? $search : null, $page, $perPage);
        $total = User::countByRole('admin', $search !== '' ? $search : null);
        $totalPages = (int) ceil($total / $perPage);
        $page = min(max(1, $page), max(1, $totalPages));

        $this->render('admin/admins_list', [
            'user' => Session::get('user'),
            'admins' => $admins,
            'csrf_token' => Csrf::generateToken(),
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
            'search' => $search,
            'page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
            'per_page' => $perPage
        ], 'Gestion des Administrateurs');
    }

    public function processDeleteStudent(): void
    {
        $this->authorize(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/students', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('admin/students', null, 'Token CSRF invalide.');
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->redirect('admin/students', null, 'ID etudiant invalide.');
        }

        $student = User::findById($id);
        if (!$student) {
            $this->redirect('admin/students', null, 'Etudiant introuvable.');
        }

        User::delete($id);
        $this->redirect('admin/students', 'Etudiant supprime avec succes.');
    }

    public function exportExerciseGrades(): void
    {
        $this->authorize(['admin']);

        $exId = (int) ($_GET['id'] ?? 0);
        if ($exId <= 0) {
            $this->redirect('admin/exercises', null, 'ID exercice invalide.');
        }

        $exercise = \App\Models\Exercise::findById($exId);
        if (!$exercise) {
            $this->redirect('admin/exercises', null, 'Exercice introuvable.');
        }

        $submissions = \App\Models\Project::getSubmissionsForExercise($exId);

        $filename = 'notes_admin_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $exercise['titre']) . '_' . date('Y-m-d_H-i') . '.txt';

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $lines = [
            'Notes ADMIN - ' . $exercise['titre'],
            'Type : ' . $exercise['type_exercice'],
            'Date limite : ' . date('d/m/Y a H:i', strtotime($exercise['date_limite'])),
            str_repeat('=', 60),
            ''
        ];

        foreach ($submissions as $sub) {
            $auteur = $sub['nom_groupe']
                ? 'Groupe : ' . $sub['nom_groupe'] . ' (Chef : ' . $sub['chef_prenom'] . ' ' . $sub['chef_nom'] . ')'
                : ($sub['ind_prenom'] . ' ' . $sub['ind_nom']);

            $lines[] = 'Auteur  : ' . $auteur;
            $lines[] = 'Projet   : ' . $sub['titre_projet'];
            $lines[] = 'Soumis le : ' . date('d/m/Y a H:i', strtotime($sub['date_soumission']));

            if ($sub['note_totale'] !== null) {
                $lines[] = 'Note     : ' . number_format((float) $sub['note_totale'], 2) . ' / 20';
                if ($sub['note_design'] !== null) {
                    $lines[] = '  Design : ' . number_format((float) $sub['note_design'], 2) . ' / 5';
                }
                if ($sub['note_code'] !== null) {
                    $lines[] = '  Code   : ' . number_format((float) $sub['note_code'], 2) . ' / 5';
                }
                if ($sub['note_fonc'] !== null) {
                    $lines[] = '  Fonct. : ' . number_format((float) $sub['note_fonc'], 2) . ' / 10';
                }
                $lines[] = 'Critique : ' . ($sub['critique_prof'] ?: '(aucune)');
                $lines[] = 'Statut   : ' . ($sub['notes_publiees'] ? 'Publiee' : 'Brouillon');
            } else {
                $lines[] = 'Note     : -- / 20 (non note)';
            }

            $lines[] = str_repeat('-', 60);
            $lines[] = '';
        }

        echo implode(PHP_EOL, $lines);
        exit;
    }

    public function exportAllExercisesGrades(): void
    {
        $this->authorize(['admin']);

        $exercises = \App\Models\Exercise::getAll();

        $filename = 'notes_globales_' . date('Y-m-d_H-i') . '.txt';

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $lines = [
            'EXPORT GLOBAL DES NOTES',
            'Genere le : ' . date('d/m/Y a H:i'),
            str_repeat('=', 60),
            ''
        ];

        foreach ($exercises as $ex) {
            $exId = (int) $ex['id_exercice'];
            $submissions = \App\Models\Project::getSubmissionsForExercise($exId);

            $lines[] = 'EXERCICE : ' . $ex['titre'];
            $lines[] = 'Type     : ' . $ex['type_exercice'];
            $lines[] = 'Limite   : ' . date('d/m/Y a H:i', strtotime($ex['date_limite']));
            $lines[] = str_repeat('-', 60);

            if (empty($submissions)) {
                $lines[] = 'Aucune soumission pour cet exercice.';
            } else {
                foreach ($submissions as $sub) {
                    $auteur = $sub['nom_groupe']
                        ? 'Groupe : ' . $sub['nom_groupe'] . ' (Chef : ' . $sub['chef_prenom'] . ' ' . $sub['chef_nom'] . ')'
                        : ($sub['ind_prenom'] . ' ' . $sub['ind_nom']);

                    $lines[] = '  Auteur  : ' . $auteur;
                    $lines[] = '  Projet   : ' . $sub['titre_projet'];
                    $lines[] = '  Soumis le : ' . date('d/m/Y a H:i', strtotime($sub['date_soumission']));

                    if ($sub['note_totale'] !== null) {
                        $lines[] = '  Note     : ' . number_format((float) $sub['note_totale'], 2) . ' / 20';
                        if ($sub['note_design'] !== null) {
                            $lines[] = '    Design : ' . number_format((float) $sub['note_design'], 2) . ' / 5';
                        }
                        if ($sub['note_code'] !== null) {
                            $lines[] = '    Code   : ' . number_format((float) $sub['note_code'], 2) . ' / 5';
                        }
                        if ($sub['note_fonc'] !== null) {
                            $lines[] = '    Fonct. : ' . number_format((float) $sub['note_fonc'], 2) . ' / 10';
                        }
                        $lines[] = '  Critique : ' . ($sub['critique_prof'] ?: '(aucune)');
                        $lines[] = '  Statut   : ' . ($sub['notes_publiees'] ? 'Publiee' : 'Brouillon');
                    } else {
                        $lines[] = '  Note     : -- / 20 (non note)';
                    }

                    $lines[] = '';
                }
            }

            $lines[] = str_repeat('=', 60);
            $lines[] = '';
        }

        echo implode(PHP_EOL, $lines);
        exit;
    }

    public function exportExerciseGradesCsv(): void
    {
        $this->authorize(['admin']);

        $exId = (int) ($_GET['id'] ?? 0);
        if ($exId <= 0) {
            $this->redirect('admin/exercises', null, 'ID exercice invalide.');
        }

        $exercise = \App\Models\Exercise::findById($exId);
        if (!$exercise) {
            $this->redirect('admin/exercises', null, 'Exercice introuvable.');
        }

        $filename = 'notes_admin_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $exercise['titre']) . '_' . date('Y-m-d_H-i') . '.txt';
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Exercice', 'Type', 'Auteur', 'Projet', 'Date soumission', 'Design', 'Code', 'Fonctionnel', 'Note totale', 'Statut', 'Critique']);

        foreach (\App\Models\Project::getSubmissionsForExercise($exId) as $sub) {
            $auteur = $sub['nom_groupe']
                ? 'Groupe : ' . $sub['nom_groupe'] . ' (Chef : ' . $sub['chef_nom'] . ' ' . $sub['chef_prenom'] . ')'
                : ($sub['ind_nom'] . ' ' . $sub['ind_prenom']);

            fputcsv($out, [
                $exercise['titre'],
                $exercise['type_exercice'],
                $auteur,
                $sub['titre_projet'],
                $sub['date_soumission'],
                $sub['note_design'] !== null ? number_format((float) $sub['note_design'], 2) : '',
                $sub['note_code'] !== null ? number_format((float) $sub['note_code'], 2) : '',
                $sub['note_fonc'] !== null ? number_format((float) $sub['note_fonc'], 2) : '',
                $sub['note_totale'] !== null ? number_format((float) $sub['note_totale'], 2) : '',
                $sub['notes_publiees'] ? 'Publiee' : 'Brouillon',
                $sub['critique_prof'] ?? ''
            ]);
        }

        fclose($out);
        exit;
    }

    public function exportAllExercisesGradesCsv(): void
    {
        $this->authorize(['admin']);

        $filename = 'notes_globales_' . date('Y-m-d_H-i') . '.txt';
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Exercice', 'Type', 'Auteur', 'Projet', 'Date soumission', 'Design', 'Code', 'Fonctionnel', 'Note totale', 'Statut', 'Critique']);

        foreach (\App\Models\Exercise::getAll() as $ex) {
            foreach (\App\Models\Project::getSubmissionsForExercise((int) $ex['id_exercice']) as $sub) {
                $auteur = $sub['nom_groupe']
                    ? 'Groupe : ' . $sub['nom_groupe'] . ' (Chef : ' . $sub['chef_nom'] . ' ' . $sub['chef_prenom'] . ')'
                    : ($sub['ind_nom'] . ' ' . $sub['ind_prenom']);

                fputcsv($out, [
                    $ex['titre'],
                    $ex['type_exercice'],
                    $auteur,
                    $sub['titre_projet'],
                    $sub['date_soumission'],
                    $sub['note_design'] !== null ? number_format((float) $sub['note_design'], 2) : '',
                    $sub['note_code'] !== null ? number_format((float) $sub['note_code'], 2) : '',
                    $sub['note_fonc'] !== null ? number_format((float) $sub['note_fonc'], 2) : '',
                    $sub['note_totale'] !== null ? number_format((float) $sub['note_totale'], 2) : '',
                    $sub['notes_publiees'] ? 'Publiee' : 'Brouillon',
                    $sub['critique_prof'] ?? ''
                ]);
            }
        }

        fclose($out);
        exit;
    }

    private function isFutureDateTime(string $value): bool
    {
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return false;
        }

        return $timestamp > time();
    }

    private function buildExerciseStats(array $exercise, int $studentCount): array
    {
        $exId = (int) $exercise['id_exercice'];
        $submissions = \App\Models\Project::getSubmissionsForExercise($exId);
        $groups = \App\Models\Group::getGroupsForExercise($exId);

        $noted = 0;
        $pending = 0;
        $late = 0;
        foreach ($submissions as &$submission) {
            $submission['author_label'] = !empty($submission['id_groupe'])
                ? (($submission['nom_groupe'] ?: 'Groupe') . ' - Chef : ' . $submission['chef_prenom'] . ' ' . $submission['chef_nom'])
                : trim((string) ($submission['ind_prenom'] . ' ' . $submission['ind_nom']));
            $submission['is_late'] = strtotime((string) $submission['date_soumission']) > strtotime((string) $exercise['date_limite']);
            if ($submission['is_late']) {
                $late++;
            }
            if ($submission['note_totale'] !== null) {
                $noted++;
            } else {
                $pending++;
            }
        }
        unset($submission);

        $expected = $exercise['type_exercice'] === 'groupe' ? count($groups) : $studentCount;
        $notSubmitted = max(0, $expected - count($submissions));

        return [
            'submissions' => $submissions,
            'groups' => $groups,
            'expected' => $expected,
            'submitted' => count($submissions),
            'noted' => $noted,
            'pending' => $pending,
            'late' => $late,
            'not_submitted' => $notSubmitted,
            'completion' => $expected > 0 ? (int) round((count($submissions) / $expected) * 100) : 0
        ];
    }

    private function notifyDerogationTarget(?int $targetUserId, ?int $targetGroupId, string $title, string $message): void
    {
        $ids = [];
        if ($targetUserId !== null && $targetUserId > 0) {
            $ids[] = $targetUserId;
        }

        if ($targetGroupId !== null && $targetGroupId > 0) {
            foreach (\App\Models\Group::getMembers($targetGroupId) as $member) {
                $ids[] = (int) $member['id_user'];
            }
        }

        \App\Models\Notification::createForUsers($ids, 'derogation', $title, $message, 'student/dashboard');
    }

    /**
     * Generate a cryptographically secure random password using random_bytes() (CSPRNG).
     * Avoids str_shuffle() which is NOT cryptographically secure.
     */
    private function generateSecurePassword(int $length = 12): string
    {
        $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#';
        $password = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        return $password;
    }
}
