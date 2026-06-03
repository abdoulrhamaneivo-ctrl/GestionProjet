<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function render(string $viewPath, array $data = [], string $title = 'EMSP Assignment Hub', array $extraScripts = []): void
    {
        extract($data);
        $h = function (mixed $value): string {
            return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
        };

        ob_start();
        $viewFile = dirname(__DIR__) . '/Views/' . $viewPath . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            throw new \Exception("La vue [{$viewPath}] n'a pas été trouvée à l'adresse: {$viewFile}");
        }
        $content = ob_get_clean();

        require dirname(__DIR__) . '/Views/layout/main.php';
    }

    protected function redirect(string $path, ?string $successFlash = null, ?string $errorFlash = null): void
    {
        if ($successFlash !== null) {
            Session::setFlash('success', $successFlash);
        }
        if ($errorFlash !== null) {
            Session::setFlash('error', $errorFlash);
        }

        session_write_close();

        $url = $path;
        if (strpos($path, 'http') !== 0) {
            $scriptName = parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH) ?: '/index.php';
            $scriptBase = rtrim(dirname($scriptName), '/');
            
            // Normalize base if rewriting hides '/public' from REQUEST_URI
            $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
            if (str_ends_with($scriptBase, '/public') && strpos($requestUri, '/public/') === false && $requestUri !== '/public') {
                $scriptBase = substr($scriptBase, 0, -7);
            }
            
            $url = $scriptBase . '/' . ltrim($path, '/');
        }

        header('Location: ' . $url);
        exit;
    }

    protected function json(mixed $data, int $statusCode = 200): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    protected function authorize(array $allowedRoles): array
    {
        $user = Session::get('user');
        if ($user === null) {
            $this->redirect('auth/login', null, 'Veuillez vous connecter pour accéder à cette page.');
        }

        if (!in_array($user['role'], $allowedRoles, true)) {
            $this->redirect('auth/login', null, "Accès interdit : Vous n'avez pas le rôle requis.");
        }

        return $user;
    }

    protected function requireLogin(): array
    {
        $user = Session::get('user');
        if ($user === null) {
            $this->redirect('auth/login', null, 'Veuillez vous connecter pour accéder à cette page.');
        }
        return $user;
    }
}
