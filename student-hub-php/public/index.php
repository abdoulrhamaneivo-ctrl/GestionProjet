<?php
/**
 * EMSP Assignment Manager - Front Controller
 * All requests route through this single entry point.
 */

declare(strict_types=1);

// 1. Load configuration dynamically depending on directory layout (public/ or root/)
if (file_exists(dirname(__DIR__) . '/config/config.php')) {
    $baseDir = dirname(__DIR__);
} else {
    $baseDir = __DIR__;
}
require_once $baseDir . '/config/config.php';

// 2. Simple PSR-4 Autoloader (No composer required for quick setup!)
spl_autoload_register(function ($class) use ($baseDir) {
    // Project-specific namespace prefix
    $prefix = 'App\\';

    // Base directory for the namespace prefix
    $base_dir = $baseDir . '/app/';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // Move to the next registered autoloader
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
    }
});

// 3. Initialize core services (Sessions & CSRF)
use App\Core\Session;
use App\Core\Router;

Session::start();

// 4. Secure basic global output header
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// 5. Dispatch Routing
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

// Normalize URI if installed in subfolder
// Uses SCRIPT_NAME (index.php path) as reliable reference — works on Windows & any docroot
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$scriptName = parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH) ?: '/index.php';
$scriptBase = rtrim(dirname($scriptName), '/');

// Normalize script base if rewriting hides '/public' from REQUEST_URI
if (str_ends_with($scriptBase, '/public') && strpos($requestUri, '/public/') === false && $requestUri !== '/public') {
    $scriptBase = substr($scriptBase, 0, -7);
}

$baseLen = strlen($scriptBase);
if ($baseLen > 0 && strncmp($requestUri, $scriptBase, $baseLen) === 0) {
    $afterBase = substr($requestUri, $baseLen);
    $requestUri = ($afterBase === '' || $afterBase === false) ? '/' : $afterBase;
}

if ($requestUri !== '/' && endsWith($requestUri, '/')) {
    $requestUri = rtrim($requestUri, '/');
}

function endsWith(string $haystack, string $needle): bool {
    $length = strlen($needle);
    if (!$length) {
        return true;
    }
    return substr($haystack, -$length) === $needle;
}

function url(string $path): string {
    static $base = null;
    if ($base === null) {
        $scriptName = parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH) ?: '/index.php';
        $base = rtrim(dirname($scriptName), '/');
        
        // Normalize base if rewriting hides '/public' from REQUEST_URI
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
        if (str_ends_with($base, '/public') && strpos($requestUri, '/public/') === false && $requestUri !== '/public') {
            $base = substr($base, 0, -7);
        }
    }
    return $base . '/' . ltrim($path, '/');
}

try {
    // Load and dispatch routes
    $router = new Router();
    
    // Register App routes
    require_once $baseDir . '/app/routes.php';
    
    $router->dispatch($requestMethod, $requestUri);
} catch (\Throwable $e) {
    // Log the error privately
    $logDir = $baseDir . '/storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    error_log('[' . date('Y-m-d H:i:s') . '] ERROR: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 3, $logDir . '/error.log');
    
    // Render custom error page
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Erreur Serveur</title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; color: #1e293b; padding: 40px; text-align: center; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0; }
            h1 { color: #dc2626; font-size: 24px; margin-bottom: 16px; }
            p { color: #64748b; line-height: 1.6; margin-bottom: 24px; }
            a { display: inline-block; background: #2563eb; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 500; }
            a:hover { background: #1d4ed8; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Une erreur technique est survenue</h1>
            <p>Désolé, l'application a rencontré un problème inattendu. L'administrateur a été notifié.</p>
            <p style="font-size: 13px; font-family: monospace; background: #f1f5f9; padding: 10px; border-radius: 6px; text-align: left; overflow: auto;">
                <strong>Message :</strong> Une erreur technique est survenue. L'administrateur a été notifié.
            </p>
            <a href="<?php echo htmlspecialchars(url(''), ENT_QUOTES, 'UTF-8'); ?>">Retour à l'accueil</a>
        </div>
    </body>
    </html>
    <?php
}
