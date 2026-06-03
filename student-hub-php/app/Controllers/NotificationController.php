<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Notification;

final class NotificationController extends Controller
{
    public function index(): void
    {
        $user = $this->requireLogin();
        $notifications = Notification::listForUser((int)$user['id_user']);

        $this->render('notifications/index', [
            'user' => $user,
            'notifications' => $notifications,
            'csrf_token' => Csrf::generateToken(),
        ], 'Notifications');
    }

    public function markAllRead(): void
    {
        $user = $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('notifications', null, 'Methode non autorisee.');
        }
        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('notifications', null, 'Token CSRF invalide.');
        }

        Notification::markAllRead((int)$user['id_user']);
        $this->redirect('notifications', 'Notifications marquees comme lues.');
    }
}
