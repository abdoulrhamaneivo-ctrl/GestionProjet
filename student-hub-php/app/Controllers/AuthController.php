<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Csrf;
use App\Core\Database;
use App\Models\User;
use App\Models\PasswordResetRequest;

final class AuthController extends Controller
{
    // MD-CHANGEPWD: flux de première connexion + changement de mot de passe obligatoire avant accès au rôle

    /**
     * Display Login view
     */
    public function showLogin(): void
    {
        $user = Session::get('user');
        if ($user !== null) {
            $this->redirectToRoleDashboard($user['role']);
        }

        $this->render('auth/login', [
            'csrf_token' => Csrf::generateToken(),
            'error' => Session::getFlash('error')
        ], 'Connexion - EMSP Hub');
    }

    public function showForgotPassword(): void
    {
        $this->render('auth/forgot_password', [
            'csrf_token' => Csrf::generateToken(),
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error')
        ], 'Mot de passe oublie - EMSP Hub');
    }

    public function processForgotPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/forgot-password', null, 'Methode non autorisee.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('auth/forgot-password', null, 'Token CSRF invalide.');
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect('auth/forgot-password', null, 'Veuillez saisir une adresse email valide.');
        }

        $user = User::findByEmail($email);
        if ($user === null) {
            $this->redirect('auth/forgot-password', 'Si ce compte existe, une demande a ete transmise a l\'administration.');
        }

        try {
            PasswordResetRequest::create((int) $user['id_user'], $email, $message);
            $this->redirect('auth/login', 'Votre demande a ete transmise. Un administrateur ou professeur pourra reinitialiser votre acces.');
        } catch (\Throwable $e) {
            error_log('[AuthController::processForgotPassword] ' . $e->getMessage());
            $this->redirect('auth/forgot-password', null, 'Erreur lors de l\'envoi de la demande.');
        }
    }

    /**
     * Process Login POST request [PRG compliant]
     */
    public function processLogin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/login', null, 'Méthode non autorisée.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('auth/login', null, 'Échec de la vérification de sécurité (Token CSRF invalide).');
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->redirect('auth/login', null, 'Veuillez remplir tous les champs requis.');
        }

        // Limitation du nombre de tentatives de connexion (anti-brute-force)
        $ipKey = 'login_attempts_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $attempts = (int) Session::get($ipKey, 0);
        $lastAttemptTime = (int) Session::get($ipKey . '_time', 0);

        // Réinitialiser après 15 minutes
        if ($lastAttemptTime > 0 && (time() - $lastAttemptTime) > 900) {
            $attempts = 0;
        }

        if ($attempts >= 10) {
            $remaining = max(0, 900 - (time() - $lastAttemptTime));
            $this->redirect('auth/login', null, 'Trop de tentatives échouées. Réessayez dans ' . ceil($remaining / 60) . ' minute(s).');
        }

        $user = User::findByEmail($email);

        if ($user === null || !password_verify($password, $user['mot_de_passe'])) {
            Session::set($ipKey, $attempts + 1);
            Session::set($ipKey . '_time', time());
            $this->redirect('auth/login', null, 'Identifiants de connexion incorrects.');
        }

        // Connexion réussie : réinitialiser le compteur
        Session::remove($ipKey);
        Session::remove($ipKey . '_time');

        session_regenerate_id(true);

        Session::set('user', [
            'id_user' => (int) $user['id_user'],
            'nom' => $user['nom'],
            'prenom' => $user['prenom'],
            'email' => $user['email'],
            'role' => $user['role']
        ]);

        // MD-CHANGEPWD: si première connexion, imposer le changement avant d’accéder au dashboard
        if (!empty($user['first_login'])) {
            $this->redirect('auth/change-password');
        }

        $this->redirectToRoleDashboard($user['role']);
    }

    /**
     * Log out session
     */
    public function processLogout(): void
    {
        Session::destroy();
        $this->redirect('auth/login', 'Vous avez été déconnecté avec succès.');
    }

    /**
     * Force first login password change
     */
    public function showChangePassword(): void
    {
        $this->requireLogin();
        $user = Session::get('user');

        $dbUser = User::findByEmail((string) $user['email']);
        if ($dbUser === null || empty($dbUser['first_login'])) {
            $this->redirectToRoleDashboard($dbUser['role'] ?? $user['role']);
        }

        $this->render('auth/change_password', [
            'csrf_token' => Csrf::generateToken(),
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error')
        ], 'Changement de mot de passe - EMSP Hub');
    }

    /**
     * Process first login password change
     */
    public function processChangePassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/login', null, 'Méthode non autorisée.');
        }

        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('auth/change-password', null, 'Échec de la vérification de sécurité (Token CSRF invalide).');
        }

        $user = Session::get('user');
        if ($user === null) {
            $this->redirect('auth/login', null, 'Vous devez être connecté.');
        }

        $dbUser = User::findByEmail((string) $user['email']);
        if ($dbUser === null || empty($dbUser['first_login'])) {
            $this->redirectToRoleDashboard($dbUser['role'] ?? $user['role']);
        }

        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $this->redirect('auth/change-password', null, 'Tous les champs sont obligatoires.');
        }

        if ($dbUser === null || !password_verify($currentPassword, $dbUser['mot_de_passe'])) {
            $this->redirect('auth/change-password', null, 'Le mot de passe actuel est incorrect.');
        }

        if ($newPassword !== $confirmPassword) {
            $this->redirect('auth/change-password', null, 'Les nouveaux mots de passe ne correspondent pas.');
        }

        if (strlen($newPassword) < 8) {
            $this->redirect('auth/change-password', null, 'Le nouveau mot de passe doit contenir au moins 8 caractères.');
        }

        try {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $db = Database::pdo();
            $stmt = $db->prepare('UPDATE utilisateurs SET mot_de_passe = ?, first_login = 0 WHERE id_user = ?');
            $stmt->execute([$hashed, (int) $user['id_user']]);

            $db->prepare('DELETE FROM import_identifiants WHERE id_user = ?')->execute([(int) $user['id_user']]);

            Session::set('success', 'Mot de passe modifié avec succès.');
            $this->redirectToRoleDashboard($user['role']);
        } catch (\Throwable $e) {
            error_log('[AuthController::processChangePassword] ' . $e->getMessage());
            $this->redirect('auth/change-password', null, 'Erreur lors du changement de mot de passe.');
        }
    }

    /**
     * Private landing redirector
     */
    private function redirectToRoleDashboard(string $role): void
    {
        switch ($role) {
            case 'admin':
                $this->redirect('admin/dashboard');
                break;
            case 'prof':
                $this->redirect('prof/dashboard');
                break;
            case 'etudiant':
                $this->redirect('student/dashboard');
                break;
            default:
                Session::destroy();
                $this->redirect('auth/login');
        }
    }
}
