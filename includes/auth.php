<?php
/**
 * Authentication Functions
 *
 * Handles user signup, login, logout, session management, and CSRF
 * protection for the application.
 *
 * User records are stored as individual JSON files in DATA_DIR/users/.
 *
 * @package School
 */

/**
 * Register a new user account.
 *
 * @param  string $username  Desired username (3-32 chars, alphanumeric + underscores).
 * @param  string $email     Valid email address.
 * @param  string $password  Password (minimum 8 characters).
 * @return array             ['success' => true, 'user' => ...] or ['error' => '...']
 */
function signup(string $username, string $email, string $password): array
{
    // ── Validation ───────────────────────────────────────────────────────────
    $username = trim($username);
    $email    = trim($email);

    if (!preg_match('/^[a-zA-Z0-9_]{3,32}$/', $username)) {
        return ['error' => 'Username must be 3-32 alphanumeric characters or underscores.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['error' => 'Invalid email address.'];
    }

    if (strlen($password) < 8) {
        return ['error' => 'Password must be at least 8 characters.'];
    }

    // ── Check for existing username ──────────────────────────────────────────
    $usersDir = DATA_DIR . '/users';
    ensureDir($usersDir);

    foreach (listFiles($usersDir, '*.json') as $file) {
        $existing = readJson($file);
        if ($existing && strtolower($existing['username']) === strtolower($username)) {
            return ['error' => 'Username already taken.'];
        }
        if ($existing && strtolower($existing['email']) === strtolower($email)) {
            return ['error' => 'Email already registered.'];
        }
    }

    // ── Create user ──────────────────────────────────────────────────────────
    $userId = generateId();
    $user   = [
        'id'           => $userId,
        'username'     => $username,
        'email'        => $email,
        'passwordHash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
        'createdAt'    => gmdate('c'),
        'settings'     => new \stdClass(), // Ensures JSON encodes as {}
    ];

    $path = $usersDir . '/' . $userId . '.json';
    if (!writeJson($path, $user)) {
        return ['error' => 'Failed to create user account.'];
    }

    // Auto-login after signup
    _setSession($user);

    // Return safe subset (no passwordHash)
    return [
        'success' => true,
        'user'    => _safeUser($user),
    ];
}

/**
 * Authenticate a user by username and password.
 *
 * @param  string $username  Username.
 * @param  string $password  Plain-text password.
 * @return array             ['success' => true, 'user' => ...] or ['error' => '...']
 */
function login(string $username, string $password): array
{
    $username = trim($username);
    $usersDir = DATA_DIR . '/users';

    if (!is_dir($usersDir)) {
        return ['error' => 'Invalid username or password.'];
    }

    foreach (listFiles($usersDir, '*.json') as $file) {
        $user = readJson($file);
        if (!$user) {
            continue;
        }

        if (strtolower($user['username']) === strtolower($username)) {
            if (password_verify($password, $user['passwordHash'])) {
                _setSession($user);
                return [
                    'success' => true,
                    'user'    => _safeUser($user),
                ];
            }
            return ['error' => 'Invalid username or password.'];
        }
    }

    return ['error' => 'Invalid username or password.'];
}

/**
 * Destroy the current session (log out).
 *
 * @return void
 */
function logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Get the currently authenticated user, or null.
 *
 * @return array|null  Safe user array without passwordHash.
 */
function getCurrentUser(): ?array
{
    if (!empty($_SESSION['user_id'])) {
        return [
            'id'       => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'email'    => $_SESSION['email'] ?? '',
        ];
    }
    return null;
}

/**
 * Require an authenticated user.
 *
 * For API requests returns a 401 JSON error; for page requests redirects
 * to /login.
 *
 * @return array  The current user data (guaranteed non-null).
 */
function requireAuth(): array
{
    $user = getCurrentUser();
    if ($user) {
        return $user;
    }

    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (str_starts_with($uri, '/api/')) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }

    header('Location: /login');
    exit;
}

// ── Internal Helpers ─────────────────────────────────────────────────────────

/**
 * Store user data in the session.
 *
 * @param  array $user  Full user record.
 * @return void
 */
function _setSession(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email']    = $user['email'];
}

/**
 * Strip sensitive fields from a user record.
 *
 * @param  array $user  Full user record.
 * @return array        Safe user record.
 */
function _safeUser(array $user): array
{
    return [
        'id'        => $user['id'],
        'username'  => $user['username'],
        'email'     => $user['email'],
        'createdAt' => $user['createdAt'],
        'settings'  => $user['settings'] ?? new \stdClass(),
    ];
}
