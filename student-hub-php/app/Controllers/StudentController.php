<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Csrf;
use App\Core\Database;
use App\Models\User;
use App\Models\Exercise;
use App\Models\Group;
use App\Models\Project;
use App\Models\Comment;
use App\Models\Notification;

final class StudentController extends Controller
{
    // MD-ARCHIVE: behaviours étudiant bloqués si exercice archivé (soumission, PDF, peer-gallery)
    /**
     * Student Dashboard page
     */
    public function dashboard(): void
    {
        $user = $this->authorize(['etudiant']);
        
        $exercises = Exercise::getActive();
        $assignmentsStatus = [];
        $hasPublishedGrade = false;

        foreach ($exercises as $ex) {
            $exId = (int) $ex['id_exercice'];
            $submitted = Project::findSubmittedProject($exId, $user['id_user']);
            $deadlineStatus = Exercise::checkSubmissionStatus($exId, $user['id_user']);
            
            if ($submitted && (int) $submitted['notes_publiees'] === 1 && $submitted['note_totale'] !== null) {
                $hasPublishedGrade = true;
            }
            
            $group = Group::findUserGroupForExercise($exId, $user['id_user']);
            
            $assignmentsStatus[] = [
                'exercice' => $ex,
                'submitted' => $submitted,
                'deadline_status' => $deadlineStatus,
                'group' => $group
            ];
        }

        $this->render('student/dashboard', [
            'user' => $user,
            'assignments' => $assignmentsStatus,
            'hasPublishedGrade' => $hasPublishedGrade,
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error')
        ], 'Tableau de bord Étudiant - EMSP');
    }

    /**
     * Show registration/submission form
     */
    public function showSubmitForm(): void
    {
        $user = $this->authorize(['etudiant']);
        
        $exId = (int) ($_GET['id'] ?? 0);
        $exercice = Exercise::findById($exId);

        if ($exercice === null) {
            $this->redirect('student/dashboard', null, 'Exercice introuvable.');
        }

        if (!empty($exercice['archive'])) {
            $this->redirect('student/dashboard', null, 'Cet exercice est archive et n\'accepte plus de soumissions.');
        }

        $deadlineCheck = Exercise::checkSubmissionStatus($exId, $user['id_user']);
        if ($deadlineCheck['status'] === 'cloture' || $deadlineCheck['status'] === 'bloque') {
            $this->redirect('student/dashboard', null, $deadlineCheck['message']);
        }

        $existing = Project::findSubmittedProject($exId, $user['id_user']);
        if ($existing && ($existing['note_totale'] !== null || (int) $existing['notes_publiees'] === 1)) {
            $this->redirect('student/dashboard', null, 'Ce projet a déjà été corrigé et ne peut plus être modifié.');
        }

        $isGroup = ($exercice['type_exercice'] === 'groupe');
        $group = null;
        $isChefAutorise = false;
        $availableMembers = [];

        if ($isGroup) {
            $group = Group::findUserGroupForExercise($exId, (int) $user['id_user']);
            if ($group !== null && (int) $group['id_chef'] !== (int) $user['id_user']) {
                $this->redirect('student/dashboard', null, 'Acces refuse : seul le chef de groupe peut deposer ou modifier le projet.');
            }

            $isChefAutorise = Exercise::isDesignatedChef($exId, (int) $user['id_user']);
            if ($group === null && !$isChefAutorise) {
                $this->redirect('student/dashboard', null, 'Acces refuse : vous n\'etes pas chef de groupe designe pour cet exercice.');
            }

            foreach (User::findAllByRole('etudiant') as $student) {
                if ((int) $student['id_user'] === (int) $user['id_user']) {
                    continue;
                }

                $student['deja_dans_groupe'] = Group::findUserGroupForExercise($exId, (int) $student['id_user']) !== null;
                $student['est_chef_designe'] = Exercise::isDesignatedChef($exId, (int) $student['id_user']);
                $availableMembers[] = $student;
            }
        }

        $this->render('student/submit', [
            'user' => $user,
            'exercice' => $exercice,
            'themes' => Exercise::getThemes($exId),
            'existing' => $existing,
            'isGroup' => $isGroup,
            'group' => $group,
            'isChefAutorise' => $isChefAutorise,
            'availableMembers' => $availableMembers,
            'csrf_token' => Csrf::generateToken(),
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error')
        ], 'Soumettre un Projet - EMSP');
    }

    public function processSubmit(): void
    {
        $user = $this->authorize(['etudiant']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('student/dashboard', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('student/dashboard', null, 'Token CSRF invalide.');
        }

        $exId = (int) ($_POST['id_exercice'] ?? 0);
        $exercice = Exercise::findById($exId);
        if ($exercice === null) {
            $this->redirect('student/dashboard', null, 'Exercice introuvable.');
        }

        if (!empty($exercice['archive'])) {
            $this->redirect('student/dashboard', null, 'Cet exercice est archive et n\'accepte plus de soumissions.');
        }

        $deadlineCheck = Exercise::checkSubmissionStatus($exId, (int) $user['id_user']);
        if ($deadlineCheck['status'] === 'cloture' || $deadlineCheck['status'] === 'bloque') {
            $this->redirect('student/dashboard', null, $deadlineCheck['message']);
        }

        $existing = Project::findSubmittedProject($exId, (int) $user['id_user']);
        if ($existing && ($existing['note_totale'] !== null || (int) $existing['notes_publiees'] === 1)) {
            $this->redirect('student/dashboard', null, 'Ce projet a deja ete corrige et ne peut plus etre modifie.');
        }

        $themeId = (int) ($_POST['id_theme'] ?? 0);
        $titreProjet = trim((string) ($_POST['titre_projet'] ?? ''));
        $lienUrl = trim((string) ($_POST['lien_url'] ?? ''));
        $accesTest = trim((string) ($_POST['acces_test'] ?? ''));
        $explications = trim((string) ($_POST['explications'] ?? ''));

        if ($titreProjet === '' || $lienUrl === '' || $accesTest === '') {
            $this->redirect('student/submit?id=' . $exId, null, 'Veuillez remplir les informations obligatoires (Titre, URL et Acces de test).');
        }

        if (!filter_var($lienUrl, FILTER_VALIDATE_URL)) {
            $this->redirect('student/submit?id=' . $exId, null, 'Veuillez fournir une URL valide.');
        }

        $isGroup = ($exercice['type_exercice'] === 'groupe');
        $group = null;
        $selectedGroupMembers = [];
        if ($isGroup) {
            $group = Group::findUserGroupForExercise($exId, (int) $user['id_user']);
            if ($group !== null && (int) $group['id_chef'] !== (int) $user['id_user']) {
                $this->redirect('student/dashboard', null, 'Acces refuse : seul le chef de groupe peut deposer ou modifier le projet.');
            }
            if ($group === null && !Exercise::isDesignatedChef($exId, (int) $user['id_user'])) {
                $this->redirect('student/dashboard', null, 'Acces refuse : vous n\'etes pas chef de groupe designe pour cet exercice.');
            }

            foreach (($_POST['group_members'] ?? []) as $memId) {
                $memId = (int) $memId;
                if ($memId <= 0 || $memId === (int) $user['id_user']) {
                    continue;
                }
                if (Group::findUserGroupForExercise($exId, $memId) !== null) {
                    $this->redirect('student/submit?id=' . $exId, null, 'Un etudiant choisi est deja dans un groupe pour cet exercice.');
                }
                if (Exercise::isDesignatedChef($exId, $memId)) {
                    $this->redirect('student/submit?id=' . $exId, null, 'Un chef de groupe ne peut pas etre choisi comme membre.');
                }
                $selectedGroupMembers[] = $memId;
            }
        }

        $uploaded_file_path = null;
        if (isset($_FILES['cahier_charges']) && $_FILES['cahier_charges']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['cahier_charges'];

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $fileMime = $finfo->file($file['tmp_name']);
            if ($fileMime !== 'application/pdf') {
                $this->redirect('student/submit?id=' . $exId, null, 'Le cahier des charges est obligatoirement au format PDF.');
            }

            if ($file['size'] > 5 * 1024 * 1024) {
                $this->redirect('student/submit?id=' . $exId, null, 'La taille du fichier ne doit pas exceder 5 Mo.');
            }

            $upload_dir = dirname(dirname(__DIR__)) . '/public/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0750, true);
            }

            $safeName = 'cdc_' . $exId . '_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $user['nom'])) . '_' . uniqid('', true) . '.pdf';
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $safeName)) {
                $uploaded_file_path = 'uploads/' . $safeName;
            } else {
                $this->redirect('student/submit?id=' . $exId, null, 'Erreur lors de l\'enregistrement du fichier.');
            }
        }

        $db = Database::pdo();

        try {
            $db->beginTransaction();

            if ($existing) {
                Project::update((int) $existing['id_projet'], [
                    'id_theme' => $themeId ?: null,
                    'titre_projet' => $titreProjet,
                    'lien_url' => $lienUrl,
                    'acces_test' => $accesTest,
                    'explications' => $explications,
                    'cahier_charges_path' => $uploaded_file_path
                ]);
                $db->commit();
                if (!empty($exercice['professeur_id'])) {
                    Notification::create((int)$exercice['professeur_id'], 'soumission', 'Rendu mis a jour', $user['prenom'] . ' ' . $user['nom'] . ' a mis a jour son rendu.', 'prof/dashboard');
                }
                $this->redirect('student/dashboard', 'Votre projet a ete mis a jour avec succes.');
            }

            $groupId = null;
            if ($isGroup) {
                if ($group !== null) {
                    $groupId = (int) $group['id_groupe'];
                } else {
                    $nomGroupe = trim((string) ($_POST['nom_groupe'] ?? ''));
                    if ($nomGroupe === '') {
                        $nomGroupe = 'Groupe de ' . $user['prenom'] . ' ' . $user['nom'];
                    }

                    $groupId = Group::create($exId, (int) $user['id_user'], $nomGroupe);
                    $insStmt = $db->prepare('INSERT IGNORE INTO membres_groupe (id_groupe, id_user) VALUES (?, ?)');

                    foreach ($selectedGroupMembers as $memId) {
                        $insStmt->execute([$groupId, $memId]);
                    }
                }
            }

            $projectId = Project::create([
                'id_exercice' => $exId,
                'id_theme' => $themeId ?: null,
                'id_user_individuel' => $isGroup ? null : (int) $user['id_user'],
                'id_groupe' => $groupId,
                'titre_projet' => $titreProjet,
                'lien_url' => $lienUrl,
                'acces_test' => $accesTest,
                'explications' => $explications,
                'cahier_charges_path' => $uploaded_file_path
            ]);

            $db->commit();
            $notifyUsers = [];
            if (!empty($exercice['professeur_id'])) {
                $notifyUsers[] = (int)$exercice['professeur_id'];
            }
            foreach (User::findAllByRole('admin') as $admin) {
                $notifyUsers[] = (int)$admin['id_user'];
            }
            Notification::createForUsers(
                $notifyUsers,
                'soumission',
                'Nouveau rendu soumis',
                $user['prenom'] . ' ' . $user['nom'] . ' a soumis "' . $titreProjet . '".',
                'prof/grade?id=' . $projectId
            );
            $this->redirect('student/dashboard', 'Felicitations, votre projet a ete soumis avec succes !');
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            if ($uploaded_file_path && file_exists(dirname(dirname(__DIR__)) . '/public/' . $uploaded_file_path)) {
                @unlink(dirname(dirname(__DIR__)) . '/public/' . $uploaded_file_path);
            }
            error_log('[StudentController::processSubmit] ' . $e->getMessage());
            $this->redirect('student/submit?id=' . $exId, null, 'Une erreur technique est survenue. Veuillez reessayer.');
        }
    }

    public function peerGallery(): void
    {
        $user = $this->authorize(['etudiant']);
        
        $projects = Project::getPublicProjectsForGallery();
        $galleryWithComments = [];

        foreach ($projects as $proj) {
            $exerciseId = (int) ($proj['id_exercice'] ?? 0);
            $exercise = $exerciseId > 0 ? \App\Models\Exercise::findById($exerciseId) : null;
            if (!empty($exercise['archive'])) {
                continue;
            }

            $comments = Comment::getCommentsByProject((int) $proj['id_projet']);
            $galleryWithComments[] = [
                'projet' => $proj,
                'comments' => $comments
            ];
        }

        $this->render('student/peer_gallery', [
            'user' => $user,
            'gallery' => $galleryWithComments,
            'csrf_token' => Csrf::generateToken(),
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error')
        ], 'Espace Peer-Testing EMSP');
    }

    /**
     * Add peer comments on a public project (supports pseudonyms for anonymous posting)
     */
    public function addPeerComment(): void
    {
        $user = $this->authorize(['etudiant']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('student/peer-gallery', null, 'Méthode non autorisée.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('student/peer-gallery', null, 'Token CSRF invalide.');
        }

        $projectId = (int) ($_POST['id_projet'] ?? 0);
        $project = Project::findById($projectId);

        if ($project === null) {
            $this->redirect('student/peer-gallery', null, 'Projet introuvable.');
        }

        $contenu = trim((string) ($_POST['comment'] ?? ''));
        $anonyme = isset($_POST['is_anonymous']);
        $pseudo = $anonyme ? 'Étudiant_' . substr(md5((string)$user['id_user']), 0, 6) : $user['prenom'] . ' ' . $user['nom'];

        if ($contenu === '') {
            $this->redirect('student/peer-gallery', null, 'Veuillez saisir un commentaire.');
        }

        try {
            Comment::create($projectId, $user['id_user'], $pseudo, $contenu);
            $this->redirect('student/peer-gallery', 'Votre retour constructif a été publié.');
        } catch (\Throwable $e) {
            error_log('[StudentController::addPeerComment] ' . $e->getMessage());
            $this->redirect('student/peer-gallery', null, 'Une erreur est survenue lors de l\'envoi.');
        }
    }

    /**
     * Generate a single-project PDF report (one project only)
     */
    public function downloadProjectPdf(): void
    {
        $user = $this->authorize(['etudiant']);

        $projectId = (int) ($_GET['id'] ?? 0);
        if ($projectId <= 0) {
            $this->redirect('student/dashboard', null, 'Identifiant de projet invalide pour le PDF.');
        }

        $project = Project::findById($projectId);
        if (!$project) {
            $this->redirect('student/dashboard', null, 'Projet introuvable.');
        }

        $exercise = \App\Models\Exercise::findById((int) ($project['id_exercice'] ?? 0));
        if ($exercise !== null && !empty($exercise['archive'])) {
            $this->redirect('student/dashboard', null, 'Cet exercice est archive.');
        }

        $isOwner = false;
        if ($project['id_user_individuel'] === $user['id_user']) {
            $isOwner = true;
        } elseif ($project['id_groupe']) {
            $group = Group::findUserGroupForExercise((int) $project['id_exercice'], $user['id_user']);
            if ($group && (int) $group['id_groupe'] === (int) $project['id_groupe']) {
                $isOwner = true;
            }
        }

        if (!$isOwner) {
            $this->redirect('student/dashboard', null, 'Vous n\'etes pas autorise a telecharger ce PDF.');
        }

        if ((int) $project['notes_publiees'] !== 1 || $project['note_totale'] === null) {
            $this->redirect('student/dashboard', null, 'La note de ce projet n\'est pas encore publiee.');
        }

        try {
            $pdf = new \App\Core\PdfReport(
                $user['nom'] . ' ' . $user['prenom'],
                $user['email']
            );

            $pdf->addProject(
                (string) ($project['exercice_titre'] ?? ''),
                $project['titre_projet'] ?? null,
                $project['nom_theme'] ?? null,
                $project['nom_groupe'] ?? null,
                $project['note_design'] !== null ? (float) $project['note_design'] : null,
                $project['note_code'] !== null ? (float) $project['note_code'] : null,
                $project['note_fonc'] !== null ? (float) $project['note_fonc'] : null,
                $project['note_totale'] !== null ? (float) $project['note_totale'] : null,
                $project['critique_prof'] ?? null
            );

            $filename = 'Fiche_Projet_' . str_replace(' ', '_', $user['nom']) . '.pdf';
            $pdf->output($filename);
        } catch (\Throwable $e) {
            error_log('[StudentController::downloadProjectPdf] ' . $e->getMessage());
            $this->redirect('student/dashboard', null, 'Erreur lors de la generation du PDF du projet.');
        }
    }

    /**
     * Generate a real server-side PDF report using FPDF
     */
    public function downloadReportPdf(): void
    {
        $user = $this->authorize(['etudiant']);

        $grades = Project::getUserGradesReport($user['id_user']);

        if (empty($grades)) {
            $this->redirect('student/dashboard', null, 'Aucune note publiee n\'est disponible pour generer votre fiche d\'evaluation.');
        }

        $archivedIds = [];
        foreach ($grades as $grade) {
            $exerciseId = (int) ($grade['id_exercice'] ?? 0);
            if ($exerciseId === 0) {
                continue;
            }
            $exercise = \App\Models\Exercise::findById($exerciseId);
            if ($exercise !== null && !empty($exercise['archive'])) {
                $archivedIds[] = $grade['id_projet'];
            }
        }

        if (!empty($archivedIds)) {
            $this->redirect('student/dashboard', null, 'Certains exercices notes sont archives et ne peuvent plus etre consultes.');
        }

        try {
            $pdf = new \App\Core\PdfReport(
                $user['nom'] . ' ' . $user['prenom'],
                $user['email']
            );

            foreach ($grades as $g) {
                $pdf->addProject(
                    (string) ($g['exercice_titre'] ?? ''),
                    $g['titre_projet'] ?? null,
                    $g['nom_theme'] ?? null,
                    $g['nom_groupe'] ?? null,
                    $g['note_design'] !== null ? (float) $g['note_design'] : null,
                    $g['note_code'] !== null ? (float) $g['note_code'] : null,
                    $g['note_fonc'] !== null ? (float) $g['note_fonc'] : null,
                    $g['note_totale'] !== null ? (float) $g['note_totale'] : null,
                    $g['critique_prof'] ?? null
                );
            }

            $filename = 'Bilan_Notes_' . str_replace(' ', '_', $user['nom']) . '.pdf';
            $pdf->output($filename);
        } catch (\Throwable $e) {
            error_log('[StudentController::downloadReportPdf] ' . $e->getMessage());
            $this->redirect('student/dashboard', null, 'Erreur lors de la generation du PDF.');
        }
    }
}
