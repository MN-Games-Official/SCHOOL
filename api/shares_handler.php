<?php
/**
 * API Handler – Shares
 *
 * Handles share creation and retrieval/verification.
 * Share viewing does NOT require authentication.
 *
 * @package School
 */

require_once __DIR__ . '/../includes/share_service.php';

/**
 * Dispatch shares actions.
 *
 * @param  string $action  One of: create, get.
 * @param  array  $params  Route parameters (may contain 'id').
 * @return void
 */
function handle_shares_handler(string $action, array $params): void
{
    switch ($action) {
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonError('Method not allowed', 405);
            }
            requireCsrf();
            $user = requireAuth();
            _handleShareCreate($user);
            break;

        case 'get':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                jsonError('Method not allowed', 405);
            }
            _handleShareGet($params['id'] ?? '');
            break;

        default:
            jsonError('Unknown shares action', 400);
    }
}

/**
 * Create a new share link.
 *
 * @param  array $user  Current user.
 * @return void
 */
function _handleShareCreate(array $user): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $missing = validateRequired($input, ['resource_id']);
    if (!empty($missing)) {
        jsonError('Missing required fields: ' . implode(', ', $missing), 422);
    }

    $result = createShare(
        $user['id'],
        sanitizeHtml($input['resource_id']),
        $input['password'] ?? null
    );

    if (isset($result['error'])) {
        jsonError($result['error'], 500);
    }

    jsonResponse($result, 201);
}

/**
 * Get shared resource data (public, no auth required).
 *
 * If the share is password-protected, password must be passed as a
 * query parameter (?password=...).
 *
 * @param  string $id  Share ID.
 * @return void
 */
function _handleShareGet(string $id): void
{
    if (empty($id)) {
        jsonError('Share ID is required', 400);
    }

    $password = $_GET['password'] ?? null;

    // Verify access
    $verify = verifyShare($id, $password);
    if (isset($verify['error'])) {
        $code = str_contains($verify['error'], 'password') ? 403 : 404;
        jsonError($verify['error'], $code);
    }

    // Load the shared data
    $data = getShareData($id);
    if (!$data || !$data['resource']) {
        jsonError('Shared resource not found', 404);
    }

    // Strip sensitive fields from the share record
    unset($data['share']['passwordHash']);

    jsonResponse($data);
}
