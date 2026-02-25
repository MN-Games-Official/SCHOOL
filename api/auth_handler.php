<?php
/**
 * API Handler – Authentication
 *
 * Handles POST /api/auth/signup, /api/auth/login, /api/auth/logout.
 *
 * @package School
 */

/**
 * Dispatch authentication actions.
 *
 * @param  string $action  One of: signup, login, logout.
 * @param  array  $params  Route parameters (unused for auth).
 * @return void
 */
function handle_auth_handler(string $action, array $params): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonError('Method not allowed', 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    switch ($action) {
        case 'signup':
            _handleSignup($input);
            break;
        case 'login':
            _handleLogin($input);
            break;
        case 'logout':
            _handleLogout();
            break;
        default:
            jsonError('Unknown auth action', 400);
    }
}

/**
 * Process a signup request.
 *
 * @param  array $input  Decoded JSON body.
 * @return void
 */
function _handleSignup(array $input): void
{
    $missing = validateRequired($input, ['username', 'email', 'password']);
    if (!empty($missing)) {
        jsonError('Missing required fields: ' . implode(', ', $missing), 422);
    }

    $result = signup($input['username'], $input['email'], $input['password']);

    if (isset($result['error'])) {
        jsonError($result['error'], 422);
    }

    jsonResponse($result, 201);
}

/**
 * Process a login request.
 *
 * @param  array $input  Decoded JSON body.
 * @return void
 */
function _handleLogin(array $input): void
{
    $missing = validateRequired($input, ['username', 'password']);
    if (!empty($missing)) {
        jsonError('Missing required fields: ' . implode(', ', $missing), 422);
    }

    $result = login($input['username'], $input['password']);

    if (isset($result['error'])) {
        jsonError($result['error'], 401);
    }

    jsonResponse($result);
}

/**
 * Process a logout request.
 *
 * @return void
 */
function _handleLogout(): void
{
    logout();
    jsonResponse(['success' => true, 'message' => 'Logged out']);
}
