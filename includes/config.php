<?php
/**
 * Application Configuration
 *
 * Centralises environment variables, directory paths, session settings,
 * and CSRF-token helpers used across the application.
 *
 * @package School
 */

// ── Environment ──────────────────────────────────────────────────────────────

/** Cerebras API key loaded from the environment or .env file. */
define('CEREBRAS_API_KEY', getenv('CEREBRAS_API_KEY') ?: '');

/** Absolute path to the data storage root (no trailing slash). */
define('DATA_DIR', __DIR__ . '/../data');

/** Whether the current request arrived over HTTPS. */
define('IS_HTTPS', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'));

// ── Session Settings ─────────────────────────────────────────────────────────

/** Session cookie lifetime in seconds (default: 7 days). */
define('SESSION_LIFETIME', 60 * 60 * 24 * 7);

/** Session name used for the cookie. */
define('SESSION_NAME', 'school_sid');

// Apply session name before session_start() is called in index.php
ini_set('session.name', SESSION_NAME);
ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);

// ── CSRF Helpers ─────────────────────────────────────────────────────────────

/**
 * Generate (or retrieve) a CSRF token for the current session.
 *
 * A new token is created once per session and stored in $_SESSION.
 *
 * @return string  The CSRF token (64 hex characters).
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token against the one stored in the session.
 *
 * @param  string $token  The token submitted by the client.
 * @return bool           True when the token is valid.
 */
function validateCsrfToken(string $token): bool
{
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Enforce CSRF validation on the current request.
 *
 * Looks for the token in the X-CSRF-Token header or the _csrf_token
 * POST field. Sends a 403 JSON response and exits on failure.
 *
 * @return void
 */
function requireCsrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? $_POST['_csrf_token']
        ?? '';

    if (!validateCsrfToken($token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Invalid or missing CSRF token']);
        exit;
    }
}
