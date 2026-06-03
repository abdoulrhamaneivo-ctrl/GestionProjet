<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Csrf;
use App\Core\Database;
use App\Models\Exercise;
use App\Models\Project;
use App\Models\Group;
use App\Models\User;
use App\Models\PasswordResetRequest;
use App\Models\Notification;

final class ProfController extends Controller
{
    // MD-ARCHIVE: notation/export bloqués si exercice archivé
    // MD-CHANGEPWD: compte prof créé via import/création : first_login=1, suppression import_identifiants après changement
    /**
     * Professor Dashboard listing assignments assigned to the logged-in professor
     */
    public function dashboard(): void
    {
        $user = $this->authorize(['prof']);
        
        $professorId = (int) $user['id_user'];
        $exercises = Exercise::getByProfesseur($professorId);
        $exercises = array_values(array_filter($exercises, static fn ($ex) => empty($ex['archive'])));
        $exercisesStats = [];

        foreach ($exercises as $ex) {
            $exId = (int) $ex['id_exercice'];
            $themes = \App\Models\Exercise::getThemes($exId);
            $themesByExercise = [];
            if (!empty($themes)) {
                foreach ($themes as $theme) {
                    $themesByExercise[] = $theme;
                }
            }
            
            $submissions = Project::getSubmissionsForExercise($exId);
            
            $notedCount = 0;
            $pendingCount = 0;
            $isPublished = false;
            $isOverdue = strtotime('now') > strtotime($ex['date_limite']);
            $isFullyGraded = false;

            foreach ($submissions as $key => &$sub) {
                $userId = (int) ($sub['id_user_individuel'] ?? $sub['id_chef'] ?? 0);
                $sub['is_late'] = false;
                if ($userId > 0) {
                    $status = Exercise::checkSubmissionStatus($exId, $userId);
                    $sub['is_late'] = ($status['status'] === 'en_retard');
                }
                
                if ($sub['note_totale'] !== null) {
                    $notedCount++;
                    if ($sub['notes_publiees'] == 1) {
                        $isPublished = true;
                    }
                } else {
                    $pendingCount++;
                }
            }
            unset($sub);

            if (count($submissions) > 0 && $notedCount === count($submissions)) {
                $isFullyGraded = true;
            }

            $statsData = [
                'exercice' => $ex,
                'themes' => $themesByExercise,
                'total_submissions' => count($submissions),
                'noted_count' => $notedCount,
                'pending_count' => $pendingCount,
                'late_count' => count(array_filter($submissions, static fn ($sub) => !empty($sub['is_late']))),
                'not_submitted_count' => $this->countNotSubmitted($ex, $submissions),
                'completion' => $this->calculateCompletion($ex, $submissions),
                'is_published' => $isPublished,
                'is_overdue' => $isOverdue,
                'is_fully_graded' => $isFullyGraded,
                'submissions' => $submissions,
                'missing' => \App\Models\Group::getMissingSubmissionsForExercise($exId)
            ];

            $exercisesStats[] = $statsData;
        }

        usort($exercisesStats, static function ($a, $b) {
            if ($a['is_published'] === $b['is_published']) {
                return strcmp($a['exercice']['titre'], $b['exercice']['titre']);
            }
            return $a['is_published'] ? 1 : -1;
        });

        $this->render('prof/dashboard', [
            'user' => $user,
            'exercises' => $exercisesStats,
            'passwordRequests' => PasswordResetRequest::getPending(),
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
            'extraScripts' => [
                ['src' => 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js']
            ]
        ], 'Espace Enseignant - EMSP');
    }

    /**
     * Show grade submission panel - supports both criteria-based and global grading
     */
    public function showGradeForm(): void
    {
        $user = $this->authorize(['prof']);
        
        $projId = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $mode = $_GET['mode'] ?? 'criteria';
        $exerciseId = isset($_GET['exercice_id']) ? (int) $_GET['exercice_id'] : 0;
        $targetUserId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
        $targetGroupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

        $project = null;
        if ($projId !== null && $projId > 0) {
            $project = Project::findById($projId);
        } elseif ($exerciseId > 0 && ($targetUserId > 0 || $targetGroupId > 0)) {
            if ($targetUserId > 0) {
                $project = Project::findSubmittedProject($exerciseId, $targetUserId);
            } elseif ($targetGroupId > 0) {
                $project = $this->findProjectByGroup($exerciseId, $targetGroupId);
            }
        }

        if ($project === null && $exerciseId > 0 && ($targetUserId > 0 || $targetGroupId > 0)) {
            $exercise = Exercise::findById($exerciseId);
            if ($exercise !== null) {
                $this->assertExerciseNotArchived($exerciseId, $exercise);
                $profId = (int) Session::get('user')['id_user'];
                if ((int) ($exercise['professeur_id'] ?? 0) !== $profId) {
                    $this->redirect('prof/dashboard', null, 'Vous n\'etes pas responsable de cet exercice.');
                }
                $nomCible = $targetUserId > 0 ? ($this->findUserName($targetUserId) ?? 'Etudiant') : ('Groupe ' . $targetGroupId);
                $projectId = $this->createPlaceholderProject($exerciseId, $targetUserId, $targetGroupId, $nomCible);
                $project = Project::findById($projectId);
            }
        }

        if ($project === null) {
            $this->redirect('prof/dashboard', null, 'Projet introuvable.');
        }

        $this->render('prof/grade', [
            'user' => $user,
            'project' => $project,
            'mode' => $mode === 'global' ? 'global' : 'criteria',
            'exercise_id' => $exerciseId,
            'target_user_id' => $targetUserId,
            'target_group_id' => $targetGroupId,
            'csrf_token' => Csrf::generateToken(),
            'error' => Session::getFlash('error')
        ], 'Évaluer Projet : ' . $project['titre_projet']);
    }

    /**
     * Submit grade / evaluation [PRG compliant]
     */
    public function processGrade(): void
    {
        $user = $this->authorize(['prof']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('prof/dashboard', null, 'Méthode non autorisée.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('prof/dashboard', null, 'Token CSRF de sécurité invalide.');
        }

        $projId = (int) ($_POST['id_projet'] ?? 0);
        $project = Project::findById($projId);

        if ($project === null) {
            $this->redirect('prof/dashboard', null, 'Projet introuvable.');
        }

        $exercise = Exercise::findById((int) ($project['id_exercice'] ?? 0));
        if ($exercise === null || !empty($exercise['archive'])) {
            $this->redirect('prof/dashboard', null, 'Cet exercice est archive.');
        }

        $noteDesign = (float) ($_POST['note_design'] ?? 0);
        $noteCode = (float) ($_POST['note_code'] ?? 0);
        $noteFonc = (float) ($_POST['note_fonc'] ?? 0);
        $critique = trim((string) ($_POST['critique_prof'] ?? ''));
        $mode = $_POST['mode'] ?? 'criteria';

        if ($mode === 'global') {
            $noteGlobale = (float) ($_POST['note_globale'] ?? 0);
            if ($noteGlobale < 0 || $noteGlobale > 20) {
                $this->redirect('prof/grade?id=' . $projId . '&mode=global', null, 'La note globale doit etre comprise entre 0 et 20.');
            }
            try {
                Project::gradeGlobal($projId, round($noteGlobale, 2), $critique);
                $isPublic = isset($_POST['statut_public']);
                Project::setPublicStatus($projId, $isPublic);
                Notification::createForUsers(Project::getResponsibleUserIds($project), 'correction', 'Note enregistree', 'Une note a ete enregistree pour "' . $project['titre_projet'] . '".', 'student/dashboard');
                $this->queuePublishPrompt((int)$project['id_exercice'], (string)($project['exercice_titre'] ?? 'cet exercice'));
                $this->redirect('prof/dashboard', 'Note globale enregistree avec succes (mode Brouillon).');
            } catch (\Throwable $e) {
                error_log('[ProfController::processGrade] ' . $e->getMessage());
                $this->redirect('prof/grade?id=' . $projId . '&mode=global', null, 'Erreur lors de l\'enregistrement de la note.');
            }
            return;
        }

        $noteDesign = round($noteDesign, 2);
        $noteCode = round($noteCode, 2);
        $noteFonc = round($noteFonc, 2);

        if ($noteDesign < 0 || $noteDesign > 5 || 
            $noteCode < 0 || $noteCode > 5 || 
            $noteFonc < 0 || $noteFonc > 10) {
            $this->redirect('prof/grade?id=' . $projId . '&mode=criteria', null, 'Les notes saisies ne respectent pas le barème défini.');
        }

        try {
            Project::grade($projId, $noteDesign, $noteCode, $noteFonc, $critique);
            
            $isPublic = isset($_POST['statut_public']);
            Project::setPublicStatus($projId, $isPublic);
            Notification::createForUsers(Project::getResponsibleUserIds($project), 'correction', 'Evaluation enregistree', 'Une evaluation a ete enregistree pour "' . $project['titre_projet'] . '".', 'student/dashboard');
            $this->queuePublishPrompt((int)$project['id_exercice'], (string)($project['exercice_titre'] ?? 'cet exercice'));

            $this->redirect('prof/dashboard', 'Évaluation enregistrée avec succès (mode Brouillon).');
        } catch (\Throwable $e) {
            error_log('[ProfController::processGrade] ' . $e->getMessage());
            $this->redirect('prof/grade?id=' . $projId . '&mode=criteria', null, 'Erreur lors de l\'enregistrement de la note.');
        }
    }

    /**
     * Confirm that corrections are finished for an exercise -> locks submissions
     */
    public function confirmCorrectionsFinished(): void
    {
        $this->authorize(['prof']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('prof/dashboard', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('prof/dashboard', null, 'Token CSRF invalide.');
        }

        $exId = (int) ($_POST['id_exercice'] ?? 0);
        if ($exId <= 0) {
            $this->redirect('prof/dashboard', null, 'ID exercice invalide.');
        }

        $exercise = Exercise::findById($exId);
        if (!$exercise) {
            $this->redirect('prof/dashboard', null, 'Exercice introuvable.');
        }

        if (!empty($exercise['archive'])) {
            $this->redirect('prof/dashboard', null, 'Cet exercice est archive.');
        }

        try {
            Exercise::markCorrectionsFinished($exId);
            $this->notifyExerciseStudents($exId, 'fin_correction', 'Corrections finalisees', 'Les corrections sont finalisees pour "' . $exercise['titre'] . '".');
            $this->redirect('prof/dashboard', 'Corrections finalisees. Les soumissions sont maintenant closes pour "' . $exercise['titre'] . '".');
        } catch (\Throwable $e) {
            error_log('[ProfController::confirmCorrectionsFinished] ' . $e->getMessage());
            $this->redirect('prof/dashboard', null, 'Erreur lors de la confirmation des corrections.');
        }
    }

    /**
     * Show form to grant a derogation for an exercise
     */
    public function showAddExemption(): void
    {
        $this->authorize(['prof']);

        $exId = (int) ($_GET['id_exercice'] ?? 0);
        $exercise = Exercise::findById($exId);

        if (!$exercise) {
            $this->redirect('prof/dashboard', null, 'Exercice introuvable.');
        }

        if (!empty($exercise['archive'])) {
            $this->redirect('prof/dashboard', null, 'Cet exercice est archive.');
        }

        $profId = (int) Session::get('user')['id_user'];
        if ((int) ($exercise['professeur_id'] ?? 0) !== $profId) {
            $this->redirect('prof/dashboard', null, 'Vous n\'etes pas responsable de cet exercice.');
        }

        $derogations = \App\Models\Exercise::getDerogationsForExercise($exId);

        $this->render('prof/add_exemption', [
            'user' => Session::get('user'),
            'exercise' => $exercise,
            'students' => User::findAllByRole('etudiant'),
            'groups' => Group::getGroupsForExercise($exId),
            'derogations' => $derogations,
            'csrf_token' => Csrf::generateToken(),
            'error' => Session::getFlash('error'),
            'success' => Session::getFlash('success')
        ], 'Accorder une derogation');
    }

    /**
     * Grant a deadline extension (derogation) for an exercise, student or group
     */
    public function processAddExemption(): void
    {
        $this->authorize(['prof']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('prof/dashboard', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('prof/dashboard', null, 'Token CSRF invalide.');
        }

        $exId = (int) ($_POST['id_exercice'] ?? 0);
        $targetUserId = (int) ($_POST['id_user_target'] ?? 0);
        $targetGroupId = (int) ($_POST['id_group_target'] ?? 0);
        $newDate = trim((string) ($_POST['nouvelle_date'] ?? ''));

        if ($exId <= 0 || $newDate === '') {
            $this->redirect('prof/dashboard', null, 'Exercice et nouvelle date sont obligatoires.');
        }

        $newDateTimestamp = strtotime($newDate);
        if ($newDateTimestamp === false || $newDateTimestamp <= time()) {
            $this->redirect('prof/dashboard', null, 'La date de derogation doit etre une date future valide.');
        }

        $exercise = Exercise::findById($exId);
        if (!$exercise) {
            $this->redirect('prof/dashboard', null, 'Exercice introuvable.');
        }

        $profId = (int) Session::get('user')['id_user'];
        if ((int) ($exercise['professeur_id'] ?? 0) !== $profId) {
            $this->redirect('prof/dashboard', null, 'Vous n\'etes pas responsable de cet exercice.');
        }

        try {
            $targetUser = $targetUserId > 0 ? $targetUserId : null;
            $targetGroup = $targetGroupId > 0 ? $targetGroupId : null;

            Exercise::addDerogation($exId, $targetUser, $targetGroup, $newDate);
            $this->notifyDerogationTarget($targetUser, $targetGroup, 'Derogation accordee', 'Une nouvelle date limite a ete accordee pour "' . $exercise['titre'] . '".');
            $this->redirect('prof/exercise/add-exemption?id_exercice=' . $exId, 'Extension de delai accordee.');
        } catch (\Throwable $e) {
            error_log('[ProfController::processAddExemption] ' . $e->getMessage());
            $this->redirect('prof/dashboard', null, 'Erreur lors de l\'enregistrement de la derogation.');
        }
    }

    public function processDeleteExemption(): void
    {
        $this->authorize(['prof']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('prof/dashboard', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('prof/dashboard', null, 'Token CSRF invalide.');
        }

        $exId = (int) ($_POST['id_exercice'] ?? 0);
        $derogationId = (int) ($_POST['id_derogation'] ?? 0);

        $exercise = Exercise::findById($exId);
        if (!$exercise) {
            $this->redirect('prof/dashboard', null, 'Exercice introuvable.');
        }

        $profId = (int) Session::get('user')['id_user'];
        if ((int) ($exercise['professeur_id'] ?? 0) !== $profId) {
            $this->redirect('prof/dashboard', null, 'Vous n\'etes pas responsable de cet exercice.');
        }

        try {
            Exercise::deleteDerogation($derogationId);
            $this->redirect('prof/exercise/add-exemption?id_exercice=' . $exId, 'Derogation supprimee avec succes.');
        } catch (\Throwable $e) {
            error_log('[ProfController::processDeleteExemption] ' . $e->getMessage());
            $this->redirect('prof/exercise/add-exemption?id_exercice=' . $exId, null, 'Erreur lors de la suppression de la derogation.');
        }
    }

    public function resolvePasswordReset(): void
    {
        $prof = $this->authorize(['prof']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('prof/dashboard', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('prof/dashboard', null, 'Token CSRF invalide.');
        }

        $requestId = (int) ($_POST['id_request'] ?? 0);
        $request = PasswordResetRequest::findPendingById($requestId);
        if ($request === null) {
            $this->redirect('prof/dashboard', null, 'Demande introuvable ou deja traitee.');
        }

        // Vérification d'autorisation : le prof ne peut réinitialiser que des étudiants
        // appartenant à ses exercices (ou des demandes dont le rôle est "etudiant")
        $db = Database::pdo();
        $isStudentOfProf = false;
        $profId = (int) $prof['id_user'];
        $targetUserId = (int) $request['id_user'];
        $targetRole = $request['role'] ?? '';

        // Les admins peuvent gérer n'importe qui; les profs seulement les étudiants liés à leurs exercices
        if ($targetRole === 'etudiant') {
            $checkStmt = $db->prepare('
                SELECT 1 FROM exercices e
                WHERE e.professeur_id = ?
                AND (
                    EXISTS (SELECT 1 FROM projets p WHERE p.id_exercice = e.id_exercice AND p.id_user_individuel = ?)
                    OR EXISTS (
                        SELECT 1 FROM groupes g WHERE g.id_exercice = e.id_exercice
                        AND (g.id_chef = ? OR EXISTS (SELECT 1 FROM membres_groupe mg WHERE mg.id_groupe = g.id_groupe AND mg.id_user = ?))
                    )
                )
                LIMIT 1
            ');
            $checkStmt->execute([$profId, $targetUserId, $targetUserId, $targetUserId]);
            $isStudentOfProf = (bool) $checkStmt->fetchColumn();
        }

        if (!$isStudentOfProf) {
            $this->redirect('prof/dashboard', null, 'Vous n'etes pas autorise a reinitialiser ce mot de passe.');
        }

        try {
            $generated = $this->generateSecurePassword(10);
            $hashed = password_hash($generated, PASSWORD_DEFAULT);
            $db = Database::pdo();
            $db->prepare('UPDATE utilisateurs SET mot_de_passe = ?, first_login = 1 WHERE id_user = ?')
                ->execute([$hashed, (int) $request['id_user']]);
            $db->prepare('INSERT INTO import_identifiants (id_user, mot_de_passe_clair) VALUES (?, ?)')
                ->execute([(int) $request['id_user'], $generated]);
            PasswordResetRequest::markResolved($requestId, (int) $prof['id_user'], $generated);

            Session::set('reset_password_result', [
                'email' => $request['email'],
                'password' => $generated
            ]);
            $this->redirect('prof/dashboard', 'Mot de passe reinitialise.');
        } catch (\Throwable $e) {
            error_log('[ProfController::resolvePasswordReset] ' . $e->getMessage());
            $this->redirect('prof/dashboard', null, 'Erreur lors de la reinitialisation.');
        }
    }

    /**
     * Publish grades for an exercise (set notes_publiees = 1)
     */
    public function publishNotes(): void
    {
        $this->authorize(['prof']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('prof/dashboard', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('prof/dashboard', null, 'Token CSRF invalide.');
        }

        $exId = (int) ($_POST['id_exercice'] ?? 0);
        if ($exId <= 0) {
            $this->redirect('prof/dashboard', null, 'ID exercice invalide.');
        }

        $exercise = Exercise::findById($exId);
        if (!$exercise) {
            $this->redirect('prof/dashboard', null, 'Exercice introuvable.');
        }

        if (!empty($exercise['archive'])) {
            $this->redirect('prof/dashboard', null, 'Cet exercice est archive.');
        }

        $profId = (int) Session::get('user')['id_user'];
        if ((int) ($exercise['professeur_id'] ?? 0) !== $profId) {
            $this->redirect('prof/dashboard', null, 'Vous n\'etes pas responsable de cet exercice.');
        }

        try {
            $db = Database::pdo();
            $stmt = $db->prepare('UPDATE projets SET notes_publiees = 1 WHERE id_exercice = ? AND note_totale IS NOT NULL');
            $stmt->execute([$exId]);
            $pending = Session::get('pending_publish_prompt');
            if (is_array($pending) && (int)($pending['id_exercice'] ?? 0) === $exId) {
                Session::remove('pending_publish_prompt');
            }
            if (isset($_POST['remember_publish_choice'])) {
                Session::set('hide_publish_prompt', true);
            }
            $this->notifyExerciseStudents($exId, 'publication_note', 'Notes publiees', 'Les notes de "' . $exercise['titre'] . '" sont maintenant disponibles.');

            $this->redirect('prof/dashboard', 'Notes publiees avec succes pour "' . $exercise['titre'] . '".');
        } catch (\Throwable $e) {
            $this->redirect('prof/dashboard', null, 'Erreur lors de la publication des notes.');
        }
    }

    public function dismissPublishPrompt(): void
    {
        $this->authorize(['prof']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('prof/dashboard', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('prof/dashboard', null, 'Token CSRF invalide.');
        }

        if (isset($_POST['remember_publish_choice'])) {
            Session::set('hide_publish_prompt', true);
        }

        Session::remove('pending_publish_prompt');
        $this->redirect('prof/dashboard', 'Notes conservees en brouillon.');
    }

    /**
     * Export grades for an exercise as plain text (prof)
     */
    public function exportExerciseGrades(): void
    {
        $this->authorize(['prof']);

        $exId = (int) ($_GET['id'] ?? 0);
        if ($exId <= 0) {
            $this->redirect('prof/dashboard', null, 'ID exercice invalide.');
        }

        $exercise = Exercise::findById($exId);
        if (!$exercise) {
            $this->redirect('prof/dashboard', null, 'Exercice introuvable.');
        }

        if (!empty($exercise['archive'])) {
            $this->redirect('prof/dashboard', null, 'Cet exercice est archive.');
        }

        $submissions = Project::getSubmissionsForExercise($exId);

        $filename = 'notes_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $exercise['titre']) . '_' . date('Y-m-d_H-i') . '.txt';

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $lines = [
            'Notes - ' . $exercise['titre'],
            'Type : ' . $exercise['type_exercice'],
            'Date limite : ' . date('d/m/Y a H:i', strtotime($exercise['date_limite'])),
            str_repeat('=', 60),
            ''
        ];

        foreach ($submissions as $sub) {
            $isGroup = !empty($sub['id_groupe']);
            $auteur = $sub['nom_groupe']
                ? 'Groupe : ' . $sub['nom_groupe'] . ' (Chef : ' . $sub['chef_nom'] . ' ' . $sub['chef_prenom'] . ')'
                : ($sub['ind_nom'] . ' ' . $sub['ind_prenom']);

            $lines[] = 'Auteur  : ' . $auteur;
            $lines[] = 'Projet   : ' . $sub['titre_projet'];
            $lines[] = 'Soumis le : ' . date('d/m/Y a H:i', strtotime($sub['date_soumission']));

            if ($isGroup) {
                $members = \App\Models\Group::getMembers((int) $sub['id_groupe']);
                if ($members) {
                    $lines[] = 'Membres : ' . implode(', ', array_map(static fn ($m) => trim($m['nom'] . ' ' . $m['prenom']), $members));
                }
            }

            if ($sub['note_totale'] !== null) {
                $lines[] = 'Note     : ' . number_format((float) $sub['note_totale'], 2) . ' / 20';
                if (!$isGroup) {
                    if ($sub['note_design'] !== null) {
                        $lines[] = '  Design : ' . number_format((float) $sub['note_design'], 2) . ' / 5';
                    }
                    if ($sub['note_code'] !== null) {
                        $lines[] = '  Code   : ' . number_format((float) $sub['note_code'], 2) . ' / 5';
                    }
                    if ($sub['note_fonc'] !== null) {
                        $lines[] = '  Fonct. : ' . number_format((float) $sub['note_fonc'], 2) . ' / 10';
                    }
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

    /**
     * Export all grades for all exercises assigned to this professor (prof)
     */
    public function exportAllExercisesGrades(): void
    {
        $this->authorize(['prof']);

        $professorId = (int) Session::get('user')['id_user'];
        $exercises = Exercise::getByProfesseur($professorId);

        $filename = 'notes_prof_globales_' . date('Y-m-d_H-i') . '.txt';

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $lines = [
            'EXPORT GLOBAL DES NOTES (PROF)',
            'Genere le : ' . date('d/m/Y à H:i'),
            str_repeat('=', 60),
            ''
        ];

        foreach ($exercises as $ex) {
            $exId = (int) $ex['id_exercice'];
            $submissions = Project::getSubmissionsForExercise($exId);

            $lines[] = 'EXERCICE : ' . $ex['titre'];
            $lines[] = 'Type     : ' . $ex['type_exercice'];
            $lines[] = 'Limite   : ' . date('d/m/Y à H:i', strtotime($ex['date_limite']));
            $lines[] = str_repeat('-', 60);

            if (empty($submissions)) {
                $lines[] = 'Aucune soumission pour cet exercice.';
            } else {
                foreach ($submissions as $sub) {
                    $isGroup = !empty($sub['id_groupe']);
                    $auteur = $sub['nom_groupe']
                        ? 'Groupe : ' . $sub['nom_groupe'] . ' (Chef : ' . $sub['chef_nom'] . ' ' . $sub['chef_prenom'] . ')'
                        : ($sub['ind_nom'] . ' ' . $sub['ind_prenom']);

                    $lines[] = 'Auteur  : ' . $auteur;
                    $lines[] = 'Projet   : ' . $sub['titre_projet'];
                    $lines[] = 'Soumis le : ' . date('d/m/Y à H:i', strtotime($sub['date_soumission']));

                    if ($isGroup) {
                        $members = \App\Models\Group::getMembers((int) $sub['id_groupe']);
                        if ($members) {
                            $lines[] = 'Membres : ' . implode(', ', array_map(static fn ($m) => trim($m['nom'] . ' ' . $m['prenom']), $members));
                        }
                    }

                    if ($sub['note_totale'] !== null) {
                        $lines[] = 'Note     : ' . number_format((float) $sub['note_totale'], 2) . ' / 20';
                        if (!$isGroup) {
                            if ($sub['note_design'] !== null) {
                                $lines[] = '  Design : ' . number_format((float) $sub['note_design'], 2) . ' / 5';
                            }
                            if ($sub['note_code'] !== null) {
                                $lines[] = '  Code   : ' . number_format((float) $sub['note_code'], 2) . ' / 5';
                            }
                            if ($sub['note_fonc'] !== null) {
                                $lines[] = '  Fonct. : ' . number_format((float) $sub['note_fonc'], 2) . ' / 10';
                            }
                        }
                        $lines[] = 'Critique : ' . ($sub['critique_prof'] ?: '(aucune)');
                        $lines[] = 'Statut   : ' . ($sub['notes_publiees'] ? 'Publiee' : 'Brouillon');
                    } else {
                        $lines[] = 'Note     : -- / 20 (non note)';
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

    /**
     * Legacy export endpoint kept as TXT if called directly.
     */
    public function exportExerciseGradesCsv(): void
    {
        $this->authorize(['prof']);

        $exId = (int) ($_GET['id'] ?? 0);
        if ($exId <= 0) {
            $this->redirect('prof/dashboard', null, 'ID exercice invalide.');
        }

        $exercise = Exercise::findById($exId);
        if (!$exercise) {
            $this->redirect('prof/dashboard', null, 'Exercice introuvable.');
        }

        $submissions = Project::getSubmissionsForExercise($exId);
        $filename = 'notes_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $exercise['titre']) . '_' . date('Y-m-d_H-i') . '.txt';

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Exercice', 'Type', 'Auteurs', 'Membres', 'Projet', 'Note /20', 'Design /5', 'Code /5', 'Fonct. /10', 'Critique', 'Statut', 'Soumis le']);

        foreach ($submissions as $sub) {
            $isGroup = !empty($sub['id_groupe']);
            $auteur = $sub['nom_groupe']
                ? 'Groupe : ' . $sub['nom_groupe'] . ' (Chef : ' . $sub['chef_nom'] . ' ' . $sub['chef_prenom'] . ')'
                : ($sub['ind_nom'] . ' ' . $sub['ind_prenom']);

            $members = '';
            if ($isGroup) {
                $memberList = \App\Models\Group::getMembers((int) $sub['id_groupe']);
                if ($memberList) {
                    $members = implode('; ', array_map(static fn ($m) => trim($m['nom'] . ' ' . $m['prenom']), $memberList));
                }
            }

            fputcsv($out, [
                $exercise['titre'],
                $exercise['type_exercice'],
                $auteur,
                $members,
                $sub['titre_projet'],
                $sub['note_totale'] !== null ? number_format((float) $sub['note_totale'], 2) : '--',
                $sub['note_design'] !== null ? number_format((float) $sub['note_design'], 2) : '',
                $sub['note_code'] !== null ? number_format((float) $sub['note_code'], 2) : '',
                $sub['note_fonc'] !== null ? number_format((float) $sub['note_fonc'], 2) : '',
                $sub['critique_prof'] ?: '(aucune)',
                $sub['notes_publiees'] ? 'Publiee' : 'Brouillon',
                date('d/m/Y à H:i', strtotime($sub['date_soumission']))
            ]);
        }

        fclose($out);
        exit;
    }

    /**
     * Legacy global export endpoint kept as TXT if called directly.
     */
    public function exportAllExercisesGradesCsv(): void
    {
        $this->authorize(['prof']);

        $professorId = (int) Session::get('user')['id_user'];
        $exercises = Exercise::getByProfesseur($professorId);

        $filename = 'notes_prof_globales_' . date('Y-m-d_H-i') . '.txt';

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Exercice', 'Type', 'Auteurs', 'Membres', 'Projet', 'Note /20', 'Design /5', 'Code /5', 'Fonct. /10', 'Critique', 'Statut', 'Soumis le']);

        foreach ($exercises as $ex) {
            $exId = (int) $ex['id_exercice'];
            $submissions = Project::getSubmissionsForExercise($exId);

            foreach ($submissions as $sub) {
                $isGroup = !empty($sub['id_groupe']);
                $auteur = $sub['nom_groupe']
                    ? 'Groupe : ' . $sub['nom_groupe'] . ' (Chef : ' . $sub['chef_nom'] . ' ' . $sub['chef_prenom'] . ')'
                    : ($sub['ind_nom'] . ' ' . $sub['ind_prenom']);

                $members = '';
                if ($isGroup) {
                    $memberList = \App\Models\Group::getMembers((int) $sub['id_groupe']);
                    if ($memberList) {
                        $members = implode('; ', array_map(static fn ($m) => trim($m['nom'] . ' ' . $m['prenom']), $memberList));
                    }
                }

                fputcsv($out, [
                    $ex['titre'],
                    $ex['type_exercice'],
                    $auteur,
                    $members,
                    $sub['titre_projet'],
                    $sub['note_totale'] !== null ? number_format((float) $sub['note_totale'], 2) : '--',
                    $sub['note_design'] !== null ? number_format((float) $sub['note_design'], 2) : '',
                    $sub['note_code'] !== null ? number_format((float) $sub['note_code'], 2) : '',
                    $sub['note_fonc'] !== null ? number_format((float) $sub['note_fonc'], 2) : '',
                    $sub['critique_prof'] ?: '(aucune)',
                    $sub['notes_publiees'] ? 'Publiee' : 'Brouillon',
                    date('d/m/Y à H:i', strtotime($sub['date_soumission']))
                ]);
            }
        }

        fclose($out);
        exit;
    }

    /**
     * Export all grades for all exercises assigned to this professor (prof)
     */
    private function findProjectByGroup(int $exerciseId, int $groupId): ?array
    {
        $db = Database::pdo();
        $sql = 'SELECT p.* FROM projets p WHERE p.id_exercice = ? AND p.id_groupe = ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->execute([$exerciseId, $groupId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Helper: abort if exercise is archived
     */
    private function assertExerciseNotArchived(int $exerciceId, array $exercise): void
    {
        if (empty($exercise['archive'])) {
            return;
        }
        $this->redirect('prof/dashboard', null, 'Cet exercice est archive.');
    }

    /**
     * Helper: find user full name by id
     */
    private function findUserName(int $userId): ?string
    {
        $db = Database::pdo();
        $stmt = $db->prepare('SELECT prenom, nom FROM utilisateurs WHERE id_user = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ? trim($row['prenom'] . ' ' . $row['nom']) : null;
    }

    /**
     * Helper: create placeholder project for manual grading when student hasn't submitted yet
     */
    private function createPlaceholderProject(int $exerciseId, ?int $userId, ?int $groupId, string $nomCible): int
    {
        $db = Database::pdo();
        $sql = 'INSERT INTO projets (id_exercice, id_theme, id_user_individuel, id_groupe, titre_projet, lien_url, acces_test, explications, date_soumission)
                VALUES (?, NULL, ?, ?, ?, \'\', \'\', ?, NOW())';
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $exerciseId,
            $userId,
            $groupId,
            'Note manuelle - ' . $nomCible,
            'Notation sans soumission.'
        ]);
        return (int) $db->lastInsertId();
    }

    private function countNotSubmitted(array $exercise, array $submissions): int
    {
        if ($exercise['type_exercice'] === 'groupe') {
            $expected = count(Group::getGroupsForExercise((int) $exercise['id_exercice']));
        } else {
            $db = Database::pdo();
            $expected = (int) $db->query('SELECT COUNT(*) FROM utilisateurs WHERE role = "etudiant"')->fetchColumn();
        }

        return max(0, $expected - count($submissions));
    }

    private function calculateCompletion(array $exercise, array $submissions): int
    {
        if ($exercise['type_exercice'] === 'groupe') {
            $expected = count(Group::getGroupsForExercise((int) $exercise['id_exercice']));
        } else {
            $db = Database::pdo();
            $expected = (int) $db->query('SELECT COUNT(*) FROM utilisateurs WHERE role = "etudiant"')->fetchColumn();
        }

        return $expected > 0 ? (int) round((count($submissions) / $expected) * 100) : 0;
    }

    private function queuePublishPrompt(int $exerciseId, string $exerciseTitle): void
    {
        if (Session::get('hide_publish_prompt', false)) {
            return;
        }

        Session::set('pending_publish_prompt', [
            'id_exercice' => $exerciseId,
            'titre' => $exerciseTitle,
        ]);
    }

    private function notifyExerciseStudents(int $exerciseId, string $type, string $title, string $message): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare('
            SELECT DISTINCT u.id_user
            FROM utilisateurs u
            LEFT JOIN projets p_ind ON p_ind.id_user_individuel = u.id_user AND p_ind.id_exercice = ?
            LEFT JOIN groupes g_chef ON g_chef.id_chef = u.id_user AND g_chef.id_exercice = ?
            LEFT JOIN membres_groupe mg ON mg.id_user = u.id_user
            LEFT JOIN groupes g_mem ON g_mem.id_groupe = mg.id_groupe AND g_mem.id_exercice = ?
            WHERE u.role = "etudiant"
            AND (p_ind.id_projet IS NOT NULL OR g_chef.id_groupe IS NOT NULL OR g_mem.id_groupe IS NOT NULL)
        ');
        $stmt->execute([$exerciseId, $exerciseId, $exerciseId]);
        $ids = array_map('intval', array_column($stmt->fetchAll(), 'id_user'));
        Notification::createForUsers($ids, $type, $title, $message, 'student/dashboard');
    }

    private function notifyDerogationTarget(?int $targetUserId, ?int $targetGroupId, string $title, string $message): void
    {
        $ids = [];
        if ($targetUserId !== null) {
            $ids[] = $targetUserId;
        }

        if ($targetGroupId !== null) {
            $db = Database::pdo();
            $stmt = $db->prepare('
                SELECT id_chef AS id_user FROM groupes WHERE id_groupe = ?
                UNION
                SELECT id_user FROM membres_groupe WHERE id_groupe = ?
            ');
            $stmt->execute([$targetGroupId, $targetGroupId]);
            foreach ($stmt->fetchAll() as $row) {
                $ids[] = (int)$row['id_user'];
            }
        }

        Notification::createForUsers($ids, 'derogation', $title, $message, 'student/dashboard');
    }

    /**
     * Generate a cryptographically secure random password using random_bytes() (CSPRNG).
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
