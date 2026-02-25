<?php
/**
 * Front Controller / Router
 *
 * Every HTTP request is funnelled through this file by .htaccess.
 * It boots the application, matches the URI against registered routes,
 * extracts path parameters, and dispatches to the correct handler.
 *
 * @package School
 */

// ── Serve static files when using PHP built-in server ────────────────────────
if (php_sapi_name() === 'cli-server') {
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $filePath   = __DIR__ . $requestUri;
    // Serve existing static files directly (js, css, images, fonts, etc.)
    if ($requestUri !== '/' && is_file($filePath)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'js'   => 'application/javascript',
            'css'  => 'text/css',
            'json' => 'application/json',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'ico'  => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2'=> 'font/woff2',
            'ttf'  => 'font/ttf',
        ];
        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
            readfile($filePath);
            return;
        }
        return false; // Let PHP built-in server handle other static files
    }
}

// ── Bootstrap ────────────────────────────────────────────────────────────────

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/storage.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validator.php';

// Start session with secure cookie parameters
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => '/',
    'domain'   => '',
    'secure'   => IS_HTTPS,
    'httponly'  => true,
    'samesite'  => 'Lax',
]);
session_start();

// ── Route Definitions ────────────────────────────────────────────────────────

/**
 * Page routes – served as HTML (handled by front-end templates).
 * {id} is a named parameter placeholder.
 */
$pageRoutes = [
    '/'                    => 'page_home',
    '/login'               => 'page_login',
    '/signup'              => 'page_signup',
    '/logout'              => 'page_logout',
    '/dashboard'           => 'page_dashboard',
    '/topics'              => 'page_topics',
    '/lessons'             => 'page_lessons',
    '/lessons/generate'    => 'page_lessons_generate',
    '/quizzes'             => 'page_quizzes',
    '/quizzes/generate'    => 'page_quizzes_generate',
    '/quizzes/take/{id}'   => 'page_quizzes_take',
    '/quizzes/review/{id}' => 'page_quizzes_review',
    '/exams'               => 'page_exams',
    '/exams/builder'       => 'page_exams_builder',
    '/exams/take/{id}'     => 'page_exams_take',
    '/exams/review/{id}'   => 'page_exams_review',
    '/writing'             => 'page_writing',
    '/writing/edit/{id}'   => 'page_writing_edit',
    '/writing/report/{id}' => 'page_writing_report',
    '/admin/topics'        => 'page_admin_topics',
    '/share/{id}'          => 'page_share',
];

/**
 * API routes – return JSON.
 * Mapped to handler files in /api/ directory.
 */
$apiRoutes = [
    // Auth
    'POST /api/auth/signup'              => ['file' => 'auth_handler.php',      'action' => 'signup'],
    'POST /api/auth/login'               => ['file' => 'auth_handler.php',      'action' => 'login'],
    'POST /api/auth/logout'              => ['file' => 'auth_handler.php',      'action' => 'logout'],

    // Topics
    'GET  /api/topics'                   => ['file' => 'topics_handler.php',    'action' => 'list'],

    // Lessons
    'POST /api/lessons/generate'         => ['file' => 'lessons_handler.php',   'action' => 'generate'],
    'GET  /api/lessons/list'             => ['file' => 'lessons_handler.php',   'action' => 'list'],
    'GET  /api/lessons/{id}'             => ['file' => 'lessons_handler.php',   'action' => 'get'],

    // Quizzes
    'POST /api/quizzes/generate'         => ['file' => 'quizzes_handler.php',   'action' => 'generate'],
    'GET  /api/quizzes/list'             => ['file' => 'quizzes_handler.php',   'action' => 'list'],
    'GET  /api/quizzes/{id}'             => ['file' => 'quizzes_handler.php',   'action' => 'get'],
    'POST /api/quizzes/{id}/grade'       => ['file' => 'quizzes_handler.php',   'action' => 'grade'],

    // Exams
    'POST /api/exams/generate-section'   => ['file' => 'exams_handler.php',     'action' => 'generate_section'],
    'GET  /api/exams/list'               => ['file' => 'exams_handler.php',     'action' => 'list'],
    'GET  /api/exams/{id}'               => ['file' => 'exams_handler.php',     'action' => 'get'],
    'POST /api/exams/{id}/grade-section' => ['file' => 'exams_handler.php',     'action' => 'grade_section'],

    // AI helpers
    'POST /api/ai/request'               => ['file' => 'ai_handler.php',        'action' => 'request'],
    'POST /api/ai/explain'               => ['file' => 'ai_handler.php',        'action' => 'explain'],
    'POST /api/ai/hint'                  => ['file' => 'ai_handler.php',        'action' => 'hint'],

    // Writing
    'GET  /api/writing/list'             => ['file' => 'writing_handler.php',   'action' => 'list'],
    'POST /api/writing/create'           => ['file' => 'writing_handler.php',   'action' => 'create'],
    'GET  /api/writing/{id}'             => ['file' => 'writing_handler.php',   'action' => 'get'],
    'POST /api/writing/{id}/save'        => ['file' => 'writing_handler.php',   'action' => 'save'],
    'POST /api/writing/{id}/scan'        => ['file' => 'writing_handler.php',   'action' => 'scan'],
    'POST /api/writing/{id}/grade'       => ['file' => 'writing_handler.php',   'action' => 'grade'],
    'POST /api/writing/{id}/snapshot'    => ['file' => 'writing_handler.php',   'action' => 'snapshot'],
    'GET  /api/writing/{id}/integrity'   => ['file' => 'writing_handler.php',   'action' => 'integrity'],

    // Shares
    'POST /api/shares/create'            => ['file' => 'shares_handler.php',    'action' => 'create'],
    'GET  /api/shares/{id}'              => ['file' => 'shares_handler.php',    'action' => 'get'],

    // Dashboard
    'GET  /api/dashboard/stats'          => ['file' => 'dashboard_handler.php', 'action' => 'stats'],
];

// ── Router ───────────────────────────────────────────────────────────────────

/**
 * Match a URI pattern (with {param} placeholders) against the actual path.
 *
 * @param  string $pattern  Route pattern, e.g. "/quizzes/take/{id}"
 * @param  string $uri      Actual request URI path
 * @return array|false      Associative array of extracted params, or false
 */
function matchRoute(string $pattern, string $uri)
{
    // Convert pattern to regex: {name} → named capture group
    $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern);
    $regex = '#^' . $regex . '$#';

    if (preg_match($regex, $uri, $matches)) {
        // Return only named captures
        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }
    return false;
}

/**
 * Send a JSON HTTP response and terminate.
 *
 * @param  mixed $data       Response payload
 * @param  int   $statusCode HTTP status code
 * @return void
 */
function jsonResponse($data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send an error JSON response and terminate.
 *
 * @param  string $message    Human-readable error message
 * @param  int    $statusCode HTTP status code
 * @return void
 */
function jsonError(string $message, int $statusCode = 400): void
{
    jsonResponse(['error' => $message], $statusCode);
}

// ── Dispatch ─────────────────────────────────────────────────────────────────

$method    = $_SERVER['REQUEST_METHOD'];
$uri       = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri       = rtrim($uri, '/') ?: '/';

// 1) Try API routes first ─────────────────────────────────────────────────────
foreach ($apiRoutes as $routeKey => $handler) {
    // $routeKey = "GET  /api/foo/{id}"
    [$routeMethod, $routePattern] = preg_split('/\s+/', $routeKey, 2);

    if ($method !== $routeMethod) {
        continue;
    }

    $params = matchRoute($routePattern, $uri);
    if ($params !== false) {
        // Load the handler file and call its entry-point function
        $handlerFile = __DIR__ . '/api/' . $handler['file'];
        if (!file_exists($handlerFile)) {
            jsonError('Handler not found', 500);
        }
        require_once $handlerFile;

        // Convention: handler file exposes handleXxx($action, $params)
        $fnName = 'handle_' . pathinfo($handler['file'], PATHINFO_FILENAME);
        if (!function_exists($fnName)) {
            jsonError('Handler function not found', 500);
        }

        try {
            $fnName($handler['action'], $params);
        } catch (\Throwable $e) {
            error_log('Unhandled exception: ' . $e->getMessage());
            jsonError('Internal server error', 500);
        }
        exit;
    }
}

// 2) Try page routes ──────────────────────────────────────────────────────────
foreach ($pageRoutes as $routePattern => $handlerName) {
    $params = matchRoute($routePattern, $uri);
    if ($params !== false) {
        $pageParams  = $params;
        $pageHandler = $handlerName;
        $currentUser = getCurrentUser();
        $csrfToken   = generateCsrfToken();

        // Map page handler names to view files
        $viewMap = [
            'page_home'             => 'landing.php',
            'page_login'            => 'auth/login.php',
            'page_signup'           => 'auth/signup.php',
            'page_logout'           => null, // handled inline
            'page_dashboard'        => 'dashboard.php',
            'page_topics'           => 'topics.php',
            'page_lessons'          => 'lessons/index.php',
            'page_lessons_generate' => 'lessons/generate.php',
            'page_quizzes'          => 'quizzes/index.php',
            'page_quizzes_generate' => 'quizzes/generate.php',
            'page_quizzes_take'     => 'quizzes/take.php',
            'page_quizzes_review'   => 'quizzes/review.php',
            'page_exams'            => 'exams/index.php',
            'page_exams_builder'    => 'exams/builder.php',
            'page_exams_take'       => 'exams/take.php',
            'page_exams_review'     => 'exams/review.php',
            'page_writing'          => 'writing/index.php',
            'page_writing_edit'     => 'writing/editor.php',
            'page_writing_report'   => 'writing/report.php',
            'page_admin_topics'     => 'admin/topics.php',
            'page_share'            => 'share.php',
        ];

        // Handle logout inline
        if ($pageHandler === 'page_logout') {
            logout();
            header('Location: /');
            exit;
        }

        // Require auth for non-guest pages
        $guestPages = ['page_home', 'page_login', 'page_signup', 'page_share'];
        if (!in_array($pageHandler, $guestPages, true)) {
            requireAuth();
        }

        // Redirect authenticated users away from login/signup
        if (in_array($pageHandler, ['page_login', 'page_signup'], true) && $currentUser) {
            header('Location: /dashboard');
            exit;
        }

        // Redirect root to dashboard if logged in
        if ($pageHandler === 'page_home' && $currentUser) {
            header('Location: /dashboard');
            exit;
        }

        $viewFile = isset($viewMap[$pageHandler])
            ? __DIR__ . '/views/' . $viewMap[$pageHandler]
            : null;

        if ($viewFile && file_exists($viewFile)) {
            header('Content-Type: text/html; charset=utf-8');
            require $viewFile;
        } else {
            // Minimal fallback
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">';
            echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
            echo '<title>School AI</title></head><body>';
            echo '<div id="app" data-page="' . htmlspecialchars($pageHandler) . '"';
            foreach ($pageParams as $k => $v) {
                echo ' data-' . htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
            }
            echo '></div>';
            echo '<script>window.__PAGE__=' . json_encode($pageHandler) . ';';
            echo 'window.__PARAMS__=' . json_encode($pageParams) . ';</script>';
            echo '<script>window.__CSRF__=' . json_encode($csrfToken) . ';</script>';
            echo '</body></html>';
        }
        exit;
    }
}

// 3) No route matched → 404 ──────────────────────────────────────────────────
http_response_code(404);
if (str_starts_with($uri, '/api/')) {
    jsonError('Not found', 404);
} else {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>404</title></head>';
    echo '<body><h1>404 — Page Not Found</h1></body></html>';
}
