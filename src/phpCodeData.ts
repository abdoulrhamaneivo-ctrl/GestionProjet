export interface FileCode {
  path: string;
  name: string;
  lang: string;
  content: string;
}

export const phpCodeFiles: FileCode[] = [
  {
    path: 'database.sql',
    name: 'database.sql',
    lang: 'sql',
    content: `-- --------------------------------------------------------
-- SQL Database Schema for student-hub-php (EMSP Assignment Manager)
-- Import this script into phpMyAdmin to create the database and seed initial data.
-- --------------------------------------------------------

CREATE DATABASE IF NOT EXISTS \`emsp_assignment_db\` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE \`emsp_assignment_db\`;

-- 1. Table: utilisateurs (Users)
CREATE TABLE IF NOT EXISTS \`utilisateurs\` (
  \`id_user\` INT AUTO_INCREMENT PRIMARY KEY,
  \`nom\` VARCHAR(50) NOT NULL,
  \`prenom\` VARCHAR(50) NOT NULL,
  \`email\` VARCHAR(100) UNIQUE NOT NULL,
  \`mot_de_passe\` VARCHAR(255) NOT NULL,
  \`role\` ENUM('etudiant', 'prof', 'admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table: exercices (Assignments / Projects guidelines)
CREATE TABLE IF NOT EXISTS \`exercices\` (
  \`id_exercice\` INT AUTO_INCREMENT PRIMARY KEY,
  \`titre\` VARCHAR(100) NOT NULL,
  \`type_exercice\` ENUM('individuel', 'groupe') NOT NULL,
  \`date_limite\` DATETIME NOT NULL,
  \`est_bloque\` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table: derogations (Individual/Group deadline extensions)
CREATE TABLE IF NOT EXISTS \`derogations\` (
  \`id_derogation\` INT AUTO_INCREMENT PRIMARY KEY,
  \`id_exercice\` INT NOT NULL,
  \`id_user\` INT DEFAULT NULL, -- Null if applies to a group
  \`id_groupe\` INT DEFAULT NULL, -- Null if applies to an individual student
  \`nouvelle_date\` DATETIME NOT NULL,
  FOREIGN KEY (\`id_exercice\`) REFERENCES \`exercices\` (\`id_exercice\`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table: themes (Project themes specified by professor / admin)
CREATE TABLE IF NOT EXISTS \`themes\` (
  \`id_theme\` INT AUTO_INCREMENT PRIMARY KEY,
  \`id_exercice\` INT NOT NULL,
  \`nom_theme\` VARCHAR(100) NOT NULL,
  FOREIGN KEY (\`id_exercice\`) REFERENCES \`exercices\` (\`id_exercice\`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table: groupes (Student Groups)
CREATE TABLE IF NOT EXISTS \`groupes\` (
  \`id_groupe\` INT AUTO_INCREMENT PRIMARY KEY,
  \`id_exercice\` INT NOT NULL,
  \`id_chef\` INT NOT NULL,
  \`nom_groupe\` VARCHAR(100) DEFAULT NULL,
  FOREIGN KEY (\`id_exercice\`) REFERENCES \`exercices\` (\`id_exercice\`) ON DELETE CASCADE,
  FOREIGN KEY (\`id_chef\`) REFERENCES \`utilisateurs\` (\`id_user\`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Table: membres_groupe (Group members)
CREATE TABLE IF NOT EXISTS \`membres_groupe\` (
  \`id_liaison\` INT AUTO_INCREMENT PRIMARY KEY,
  \`id_groupe\` INT NOT NULL,
  \`id_user\` INT NOT NULL,
  UNIQUE KEY \`idx_grp_user\` (\`id_groupe\`, \`id_user\`),
  FOREIGN KEY (\`id_groupe\`) REFERENCES \`groupes\` (\`id_groupe\`) ON DELETE CASCADE,
  FOREIGN KEY (\`id_user\`) REFERENCES \`utilisateurs\` (\`id_user\`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Table: projets (Project submissions and grades)
CREATE TABLE IF NOT EXISTS \`projets\` (
  \`id_projet\` INT AUTO_INCREMENT PRIMARY KEY,
  \`id_exercice\` INT NOT NULL,
  \`id_theme\` INT DEFAULT NULL,
  \`id_user_individuel\` INT DEFAULT NULL, -- Filled if individual assignment
  \`id_groupe\` INT DEFAULT NULL,          -- Filled if group assignment
  \`titre_projet\` VARCHAR(150) NOT NULL,
  \`lien_url\` VARCHAR(255) NOT NULL,
  \`acces_test\` TEXT NOT NULL,            -- Test credentials
  \`explications\` TEXT,                  -- How it works, context
  \`cahier_charges_path\` VARCHAR(255) DEFAULT NULL, -- Uploaded PDF path
  \`note_design\` DECIMAL(4,2) DEFAULT NULL, -- Out of 5
  \`note_code\` DECIMAL(4,2) DEFAULT NULL,   -- Out of 5
  \`note_fonc\` DECIMAL(4,2) DEFAULT NULL,   -- Out of 10
  \`note_totale\` DECIMAL(4,2) DEFAULT NULL, -- Calculated / 20
  \`critique_prof\` TEXT DEFAULT NULL,
  \`notes_publiees\` TINYINT(1) DEFAULT 0,
  \`statut_public\` TINYINT(1) DEFAULT 0,    -- Visible to other students for peer testing
  \`date_soumission\` DATETIME NOT NULL,
  FOREIGN KEY (\`id_exercice\`) REFERENCES \`exercices\` (\`id_exercice\`) ON DELETE CASCADE,
  FOREIGN KEY (\`id_theme\`) REFERENCES \`themes\` (\`id_theme\`) ON DELETE SET NULL,
  FOREIGN KEY (\`id_user_individuel\`) REFERENCES \`utilisateurs\` (\`id_user\`) ON DELETE SET NULL,
  FOREIGN KEY (\`id_groupe\`) REFERENCES \`groupes\` (\`id_groupe\`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Table: commentaires (Peer reviews / Comments)
CREATE TABLE IF NOT EXISTS \`commentaires\` (
  \`id_commentaire\` INT AUTO_INCREMENT PRIMARY KEY,
  \`id_projet\` INT NOT NULL,
  \`id_user\` INT NOT NULL,
  \`pseudonyme\` VARCHAR(50) DEFAULT NULL, -- Anonymous pseudonyms for student review
  \`contenu\` TEXT NOT NULL,
  \`date_publication\` DATETIME NOT NULL,
  FOREIGN KEY (\`id_projet\`) REFERENCES \`projets\` (\`id_projet\`) ON DELETE CASCADE,
  FOREIGN KEY (\`id_user\`) REFERENCES \`utilisateurs\` (\`id_user\`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;`
  },
  {
    path: 'config/config.php',
    name: 'config.php',
    lang: 'php',
    content: `<?php
/**
 * Configuration file for EMSP Assignment Manager
 * Correct database credentials should be provided here when installing.
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'emsp_assignment_db');

// App settings
define('APP_NAME', 'EMSP Assignment Manager');
define('APP_URL', 'http://localhost/student-hub-php'); // Update this to match your local XAMPP/WAMP URL

// Security settings
define('SESSION_LIFETIME', 3600); // 1 hour

// Autoload paths
define('APP_PATH', dirname(__DIR__) . '/app');`
  },
  {
    path: 'public/index.php',
    name: 'index.php',
    lang: 'php',
    content: `<?php
/**
 * EMSP Assignment Manager - Front Controller
 * All requests route through this single entry point.
 */

declare(strict_types=1);

// 1. Load configuration
require_once dirname(__DIR__) . '/config/config.php';

// 2. Simple Autoloader PSR-4
spl_autoload_register(function ($class) {
    $prefix = 'App\\\\';
    $base_dir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// 3. Initialize core services
use App\\Core\\Session;
use App\\Core\\Router;

Session::start();

// 4. Secure basic global output headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

// Core URL dispatch routing...`
  },
  {
    path: 'app/routes.php',
    name: 'routes.php',
    lang: 'php',
    content: `<?php
/**
 * EMSP Assignment Manager - MVC Route Registry
 */

declare(strict_types=1);

/** @var \\App\\Core\\Router $router */

// Auth routes
$router->get('/', [App\\Controllers\\AuthController::class, 'showLogin']);
$router->get('/auth/login', [App\\Controllers\\AuthController::class, 'showLogin']);
$router->post('/auth/login', [App\\Controllers\\AuthController::class, 'processLogin']);
$router->get('/auth/logout', [App\\Controllers\\AuthController::class, 'processLogout']);

// Student routes
$router->get('/student/dashboard', [App\\Controllers\\StudentController::class, 'dashboard']);
$router->get('/student/submit', [App\\Controllers\\StudentController::class, 'showSubmitForm']);
$router->post('/student/submit', [App\\Controllers\\StudentController::class, 'processSubmit']);
$router->get('/student/peer-gallery', [App\\Controllers\\StudentController::class, 'peerGallery']);
$router->post('/student/peer-gallery/comment', [App\\Controllers\\StudentController::class, 'addPeerComment']);
$router->get('/student/report-pdf', [App\\Controllers\\StudentController::class, 'downloadReportPdf']);

// Professor routes
$router->get('/prof/dashboard', [App\\Controllers\\ProfController::class, 'dashboard']);
$router->get('/prof/grade', [App\\Controllers\\ProfController::class, 'showGradeForm']);
$router->post('/prof/grade', [App\\Controllers\\ProfController::class, 'processGrade']);
$router->post('/prof/publish-notes', [App\\Controllers\\ProfController::class, 'publishNotes']);

// Admin routes
$router->get('/admin/dashboard', [App\\Controllers\\AdminController::class, 'dashboard']);
$router->get('/admin/exercise/create', [App\\Controllers\\AdminController::class, 'showCreateExercise']);
$router->post('/admin/exercise/create', [App\\Controllers\\AdminController::class, 'processCreateExercise']);
$router->get('/admin/group/manage', [App\\Controllers\\AdminController::class, 'showManageGroups']);
$router->post('/admin/group/create', [App\\Controllers\\AdminController::class, 'processCreateGroup']);
$router->post('/admin/exemption/add', [App\\Controllers\\AdminController::class, 'processAddExemption']);`
  },
  {
    path: 'app/Core/Router.php',
    name: 'Router.php',
    lang: 'php',
    content: `<?php
declare(strict_types=1);

namespace App\\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }

        if (isset($this->routes[$method][$uri])) {
            $handler = $this->routes[$method][$uri];
            $controllerClass = $handler[0];
            $action = $handler[1];

            if (class_exists($controllerClass)) {
                $controller = new $controllerClass();
                if (method_exists($controller, $action)) {
                    $controller->$action();
                    return;
                }
            }
        }

        // Render 404 response
        http_response_code(404);
        require dirname(__DIR__) . '/Views/errors/404.php';
    }
}`
  },
  {
    path: 'app/Core/Controller.php',
    name: 'Controller.php',
    lang: 'php',
    content: `<?php
declare(strict_types=1);

namespace App\\Core;

abstract class Controller
{
    protected function render(string $viewPath, array $data = [], string $title = 'EMSP Hub'): void
    {
        extract($data);
        $h = function (mixed $value): string {
            return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
        };

        ob_start();
        require dirname(__DIR__) . '/Views/' . $viewPath . '.php';
        $content = ob_get_clean();

        require dirname(__DIR__) . '/Views/layout/main.php';
    }

    protected function redirect(string $path, ?string $successFlash = null, ?string $errorFlash = null): void
    {
        if ($successFlash !== null) Session::setFlash('success', $successFlash);
        if ($errorFlash !== null) Session::setFlash('error', $errorFlash);

        session_write_close();
        header('Location: ' . APP_URL . '/public/' . ltrim($path, '/'));
        exit;
    }

    protected function authorize(array $allowedRoles): array
    {
        $user = Session::get('user');
        if ($user === null || !in_array($user['role'], $allowedRoles, true)) {
            $this->redirect('auth/login', null, 'Accès interdit ou session expirée.');
        }
        return $user;
    }
}`
  },
  {
    path: 'app/Core/Session.php',
    name: 'Session.php',
    lang: 'php',
    content: `<?php
declare(strict_types=1);

namespace App\\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function setFlash(string $key, string $message): void
    {
        $_SESSION['flash'][$key] = $message;
    }

    public static function getFlash(string $key): ?string
    {
        if (isset($_SESSION['flash'][$key])) {
            $msg = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $msg;
        }
        return null;
    }
}`
  },
  {
    path: 'app/Core/Csrf.php',
    name: 'Csrf.php',
    lang: 'php',
    content: `<?php
declare(strict_types=1);

namespace App\\Core;

final class Csrf
{
    public static function generateToken(): string
    {
        $token = Session::get('csrf_token');
        if ($token === null) {
            $token = bin2hex(random_bytes(32));
            Session::set('csrf_token', $token);
        }
        return $token;
    }

    public static function verifyToken(?string $token): bool
    {
        $sessionToken = Session::get('csrf_token');
        return $sessionToken !== null && $token !== null && hash_equals($sessionToken, $token);
    }
}`
  },
  {
    path: 'app/Models/Project.php',
    name: 'Project.php',
    lang: 'php',
    content: `<?php
declare(strict_types=1);

namespace App\\Models;

use App\\Core\\Database;
use PDO;

final class Project
{
    public static function create(array $data): int
    {
        $db = Database::pdo();
        $sql = 'INSERT INTO projets (id_exercice, id_theme, id_user_individuel, id_groupe, titre_projet, lien_url, acces_test, explications, cahier_charges_path, date_soumission) 
                VALUES (:id_exercice, :id_theme, :id_user_individuel, :id_groupe, :titre_projet, :lien_url, :acces_test, :explications, :cahier_charges_path, :date_soumission)';
        $stmt = $db->prepare($sql);
        $stmt->execute($data);
        return (int) $db->lastInsertId();
    }

    public static function grade(int $projectId, float $design, float $code, float $fonc, ?string $critique): void
    {
        $db = Database::pdo();
        $totale = $design + $code + $fonc;
        $sql = 'UPDATE projets SET note_design = ?, note_code = ?, note_fonc = ?, note_totale = ?, critique_prof = ? WHERE id_projet = ?';
        $db->prepare($sql)->execute([$design, $code, $fonc, $totale, $critique, $projectId]);
    }
}`
  },
  {
    path: 'app/Controllers/StudentController.php',
    name: 'StudentController.php',
    lang: 'php',
    content: `<?php
declare(strict_types=1);

namespace App\\Controllers;

use App\\Core\\Controller;
use App\\Core\\Session;
use App\\Core\\Csrf;
use App\\Models\\Project;
use App\\Models\\Group;

final class StudentController extends Controller
{
    public function processSubmit(): void
    {
        $user = $this->authorize(['etudiant']);
        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('student/submit', null, 'Token CSRF invalide.');
        }

        // Parse metrics, validate PDF formats with mime_content_type...
        // Begin transaction, create project, redirect [PRG compliant]
        $this->redirect('student/dashboard', 'Projet soumis avec succès !');
    }
}`
  }
];
