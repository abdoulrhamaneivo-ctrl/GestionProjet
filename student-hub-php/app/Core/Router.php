<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    /**
     * Map a GET request route
     */
    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    /**
     * Map a POST request route
     */
    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    /**
     * Dispatch matching routes
     */
    public function dispatch(string $method, string $uri): void
    {
        // Clean trailing slash for matching
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }

        // Check if route exists for the current HTTP verb
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
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Page Non Trouvée - 404</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; color: #1e293b; padding: 100px 40px; text-align: center; }
                .container { max-width: 500px; margin: 0 auto; background: white; padding: 50px 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0; }
                h1 { color: #1e3a8a; font-size: 72px; margin: 0; font-weight: 800; line-height: 1; }
                h2 { color: #334155; font-size: 20px; margin: 20px 0 10px; }
                p { color: #64748b; line-height: 1.6; margin-bottom: 30px; }
                a { display: inline-block; background: #2563eb; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 500; font-size: 15px; }
                a:hover { background: #1d4ed8; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>404</h1>
                <h2>Page non trouvée</h2>
                <p>La page que vous recherchez n'existe pas ou a été déplacée par l'administrateur de l'EMSP.</p>
                <a href="<?php
                    $scriptName = parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH) ?: '/index.php';
                    $scriptBase = rtrim(dirname($scriptName), '/');
                    echo htmlspecialchars($scriptBase . '/', ENT_QUOTES, 'UTF-8');
                ?>">Page d'accueil</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
