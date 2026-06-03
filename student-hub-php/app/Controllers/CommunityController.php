<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Project;

final class CommunityController extends Controller
{
    public function index(): void
    {
        $projects = Project::getPublicProjectsForGallery();
        $filters = [
            'theme' => trim((string)($_GET['theme'] ?? '')),
            'exercise' => trim((string)($_GET['exercise'] ?? '')),
            'type' => trim((string)($_GET['type'] ?? '')),
        ];

        $projects = array_values(array_filter($projects, static function (array $project) use ($filters): bool {
            if ($filters['theme'] !== '' && stripos((string)($project['nom_theme'] ?? ''), $filters['theme']) === false) {
                return false;
            }
            if ($filters['exercise'] !== '' && stripos((string)($project['exercice_titre'] ?? ''), $filters['exercise']) === false) {
                return false;
            }
            if ($filters['type'] === 'groupe' && empty($project['id_groupe'])) {
                return false;
            }
            if ($filters['type'] === 'individuel' && !empty($project['id_groupe'])) {
                return false;
            }
            return true;
        }));

        $this->render('community/index', [
            'user' => Session::get('user'),
            'projects' => $projects,
            'filters' => $filters,
            'csrf_token' => Csrf::generateToken(),
        ], 'Communaute DSER PROJECT');
    }

    public function show(): void
    {
        $projectId = (int)($_GET['id'] ?? 0);
        $project = Project::findPublicProject($projectId);
        if (!$project) {
            $this->redirect('community', null, 'Projet introuvable ou pas encore public.');
        }

        $currentUser = Session::get('user');
        $isResponsible = $currentUser !== null && Project::userIsResponsible($projectId, (int)$currentUser['id_user']);

        $this->render('community/show', [
            'user' => $currentUser,
            'project' => $project,
            'comments' => Comment::getThreadByProject($projectId),
            'isResponsible' => $isResponsible,
            'csrf_token' => Csrf::generateToken(),
        ], 'Projet communautaire');
    }

    public function comment(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('community', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('community', null, 'Token CSRF invalide.');
        }

        $projectId = (int)($_POST['id_projet'] ?? 0);
        $project = Project::findPublicProject($projectId);
        if (!$project) {
            $this->redirect('community', null, 'Projet introuvable ou pas encore public.');
        }

        if (trim((string)($_POST['website'] ?? '')) !== '') {
            $this->redirect('community/project?id=' . $projectId, 'Commentaire recu.');
        }

        if (Comment::countRecentFromSession('community_comment_last_' . $projectId, 20)) {
            $this->redirect('community/project?id=' . $projectId, null, 'Veuillez patienter avant de publier un autre commentaire.');
        }

        $content = trim((string)($_POST['contenu'] ?? ''));
        $visitorName = trim((string)($_POST['nom_visiteur'] ?? ''));
        $currentUser = Session::get('user');
        if ($currentUser !== null && $visitorName === '') {
            $visitorName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
        }

        if ($visitorName === '' || strlen($visitorName) > 50) {
            $this->redirect('community/project?id=' . $projectId, null, 'Veuillez indiquer un pseudonyme de 1 a 50 caracteres.');
        }
        if ($content === '' || strlen($content) > 1200) {
            $this->redirect('community/project?id=' . $projectId, null, 'Le commentaire doit contenir entre 1 et 1200 caracteres.');
        }

        Comment::createPublic($projectId, $currentUser['id_user'] ?? null, $visitorName, $content);
        $_SESSION['community_comment_last_' . $projectId] = time();

        Notification::createForUsers(
            Project::getResponsibleUserIds($project),
            'commentaire',
            'Nouveau commentaire sur votre projet',
            $visitorName . ' a commente "' . $project['titre_projet'] . '".',
            'community/project?id=' . $projectId
        );

        $this->redirect('community/project?id=' . $projectId, 'Commentaire publie.');
    }

    public function reply(): void
    {
        $user = $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('community', null, 'Methode non autorisee.');
        }
        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('community', null, 'Token CSRF invalide.');
        }

        $projectId = (int)($_POST['id_projet'] ?? 0);
        $parentId = (int)($_POST['parent_id'] ?? 0);
        $project = Project::findPublicProject($projectId);
        if (!$project || $parentId <= 0) {
            $this->redirect('community', null, 'Reponse impossible.');
        }
        if (!Project::userIsResponsible($projectId, (int)$user['id_user'])) {
            $this->redirect('community/project?id=' . $projectId, null, 'Seuls les responsables du projet peuvent repondre officiellement.');
        }

        $content = trim((string)($_POST['contenu'] ?? ''));
        if ($content === '' || strlen($content) > 1200) {
            $this->redirect('community/project?id=' . $projectId, null, 'La reponse doit contenir entre 1 et 1200 caracteres.');
        }

        Comment::createPublic($projectId, (int)$user['id_user'], $user['prenom'] . ' ' . $user['nom'], $content, $parentId, true);
        $this->redirect('community/project?id=' . $projectId, 'Reponse publiee.');
    }
}
