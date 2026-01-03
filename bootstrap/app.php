<?php
/**
 * Application Bootstrap
 * Initialize the application and handle requests
 */

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Helpers/AuditService.php';
require_once __DIR__ . '/../app/Helpers/AuthHelper.php';

// Load middleware classes
require_once __DIR__ . '/../app/Middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../app/Middlewares/PermissionMiddleware.php';

// Set timezone
date_default_timezone_set(Env::get('APP_TIMEZONE', 'Africa/Nairobi'));

// Error handling
if (Env::isDebug()) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', Env::get('STORAGE_PATH') . '/logs/php_errors.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', Env::get('STORAGE_PATH') . '/logs/php_errors.log');
}

// Custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    logMessage("Error [{$errno}]: {$errstr} in {$errfile}:{$errline}", 'error');
    if (Env::isDebug()) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
});

// Custom exception handler
set_exception_handler(function($exception) {
    logMessage("Exception: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine(), 'error');

    // Log full trace
    logMessage("Stack trace: " . $exception->getTraceAsString(), 'error');

    if (Env::isDebug()) {
        // Beautiful error page for debug mode
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Exception - <?= htmlspecialchars($exception->getMessage()) ?></title>
            <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css" rel="stylesheet"/>
            <style>
                body { background: #f5f5f5; padding: 20px; }
                .error-container { max-width: 1200px; margin: 0 auto; }
                .trace-line { font-family: monospace; font-size: 13px; padding: 5px; border-left: 3px solid #ccc; margin: 5px 0; }
                .trace-line:hover { background: #f0f0f0; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="card card-lg">
                    <div class="card-header bg-danger text-white">
                        <h2 class="card-title"><i class="ti ti-alert-triangle"></i> Exception</h2>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger mb-3">
                            <h3><?= htmlspecialchars($exception->getMessage()) ?></h3>
                        </div>

                        <div class="mb-3">
                            <strong>File:</strong>
                            <code><?= htmlspecialchars($exception->getFile()) ?></code>
                            <strong>Line:</strong>
                            <code><?= $exception->getLine() ?></code>
                        </div>

                        <h4>Stack Trace:</h4>
                        <div style="background: #1e1e1e; color: #dcdcdc; padding: 15px; border-radius: 5px; overflow-x: auto;">
                            <pre style="margin: 0; color: #dcdcdc;"><?= htmlspecialchars($exception->getTraceAsString()) ?></pre>
                        </div>

                        <div class="mt-3">
                            <a href="javascript:history.back()" class="btn btn-primary">
                                <i class="ti ti-arrow-left"></i> Go Back
                            </a>
                            <a href="/dashboard" class="btn btn-secondary">
                                <i class="ti ti-home"></i> Dashboard
                            </a>
                        </div>
                    </div>
                    <div class="card-footer text-muted">
                        <small>This detailed error is shown because APP_DEBUG=true in .env</small>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    } else {
        http_response_code(500);
        echo "An error occurred. Please try again later.";
    }
});

/**
 * Router Class
 * Simple routing system
 */
class Router
{
    private static $routes = [];

    public static function get($path, $handler) {
        self::$routes['GET'][$path] = $handler;
    }

    public static function post($path, $handler) {
        self::$routes['POST'][$path] = $handler;
    }

    public static function any($path, $handler) {
        self::$routes['GET'][$path] = $handler;
        self::$routes['POST'][$path] = $handler;
    }

    public static function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove trailing slash except for root
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }

        // Check for exact match
        if (isset(self::$routes[$method][$uri])) {
            return self::execute(self::$routes[$method][$uri]);
        }

        // Check for dynamic routes
        foreach (self::$routes[$method] ?? [] as $route => $handler) {
            $pattern = self::convertRouteToRegex($route);
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove full match
                // Filter to only numeric keys for positional arguments (PHP 8+ compatibility)
                $params = array_values(array_filter($matches, 'is_numeric', ARRAY_FILTER_USE_KEY));
                return self::execute($handler, $params);
            }
        }

        // 404 Not Found
        http_response_code(404);
        echo "404 - Page Not Found";
    }

    private static function convertRouteToRegex($route) {
        // Convert :param to named capture groups
        $pattern = preg_replace('/\/:([^\/]+)/', '/(?P<$1>[^/]+)', $route);
        return '#^' . $pattern . '$#';
    }

    private static function execute($handler, $params = []) {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }

        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controllerName, $method) = explode('@', $handler);
            $controllerFile = __DIR__ . '/../app/Controllers/' . $controllerName . '.php';

            if (file_exists($controllerFile)) {
                require_once $controllerFile;
                $controller = new $controllerName();
                if (method_exists($controller, $method)) {
                    return call_user_func_array([$controller, $method], $params);
                }
            }
        }

        http_response_code(500);
        echo "Handler not found";
    }
}

/**
 * Request Class
 * Handle HTTP requests
 */
class Request
{
    public static function all() {
        return array_merge($_GET, $_POST);
    }

    public static function get($key, $default = null) {
        return self::all()[$key] ?? $default;
    }

    public static function post($key, $default = null) {
        return $_POST[$key] ?? $default;
    }

    public static function has($key) {
        return isset(self::all()[$key]);
    }

    public static function only(...$keys) {
        $data = [];
        foreach ($keys as $key) {
            if (self::has($key)) {
                $data[$key] = self::get($key);
            }
        }
        return $data;
    }

    public static function method() {
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function isPost() {
        return self::method() === 'POST';
    }

    public static function isGet() {
        return self::method() === 'GET';
    }

    public static function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public static function ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    public static function userAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
}

/**
 * Response Class
 * Handle HTTP responses
 */
class Response
{
    public static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function redirect($url, $statusCode = 302) {
        header("Location: {$url}", true, $statusCode);
        exit;
    }

    public static function back() {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        self::redirect($referer);
    }

    public static function view($viewPath, $data = []) {
        extract($data);
        $viewFile = __DIR__ . '/../app/Views/' . str_replace('.', '/', $viewPath) . '.php';

        if (!file_exists($viewFile)) {
            throw new Exception("View not found: {$viewPath}");
        }

        // Check if this is an AJAX request
        $isAjax = self::isAjax();

        if ($isAjax) {
            // For AJAX requests, render only the content without layout
            // Check if there's a _content.php variant (with underscore prefix)
            $viewDir = dirname(__DIR__ . '/../app/Views/' . str_replace('.', '/', $viewPath) . '.php');
            $viewName = basename(str_replace('.', '/', $viewPath));
            $contentFile = $viewDir . '/_' . $viewName . '_content.php';

            if (file_exists($contentFile)) {
                // Render content-only file
                require $contentFile;
            } else {
                // Render the full view (it will use layout, but client will extract content)
                require $viewFile;
            }
        } else {
            // Normal request - render with layout
            require $viewFile;
        }
    }

    public static function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ||
               !empty($_SERVER['HTTP_X_AJAX_NAVIGATION']);
    }
}

/**
 * Middleware Interface
 */
interface Middleware
{
    public function handle($next);
}
