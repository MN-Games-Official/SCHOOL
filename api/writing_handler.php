<?php
/**
 * API Handler – Writing
 *
 * Handles CRUD, AI scanning, grading, snapshots, and integrity for
 * writing documents.
 *
 * @package School
 */

require_once __DIR__ . '/../includes/ai_gateway.php';
require_once __DIR__ . '/../includes/writing_service.php';

/**
 * Dispatch writing actions.
 *
 * @param  string $action  One of: list, create, get, save, scan, grade, snapshot, integrity.
 * @param  array  $params  Route parameters (may contain 'id').
 * @return void
 */
function handle_writing_handler(string $action, array $params): void
{
    $user = requireAuth();

    switch ($action) {
        case 'list':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                jsonError('Method not allowed', 405);
            }
            _handleWritingList($user);
            break;

        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonError('Method not allowed', 405);
            }
            requireCsrf();
            _handleWritingCreate($user);
            break;

        case 'get':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                jsonError('Method not allowed', 405);
            }
            _handleWritingGet($user, $params['id'] ?? '');
            break;

        case 'save':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonError('Method not allowed', 405);
            }
            requireCsrf();
            _handleWritingSave($user, $params['id'] ?? '');
            break;

        case 'scan':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonError('Method not allowed', 405);
            }
            requireCsrf();
            _handleWritingScan($user, $params['id'] ?? '');
            break;

        case 'grade':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonError('Method not allowed', 405);
            }
            requireCsrf();
            _handleWritingGrade($user, $params['id'] ?? '');
            break;

        case 'snapshot':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonError('Method not allowed', 405);
            }
            requireCsrf();
            _handleWritingSnapshot($user, $params['id'] ?? '');
            break;

        case 'integrity':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                jsonError('Method not allowed', 405);
            }
            _handleWritingIntegrity($user, $params['id'] ?? '');
            break;

        default:
            jsonError('Unknown writing action', 400);
    }
}

/**
 * List all writing documents for the current user.
 *
 * @param  array $user  Current user.
 * @return void
 */
function _handleWritingList(array $user): void
{
    $dir   = DATA_DIR . '/writing/' . $user['id'];
    $files = listFiles($dir, '*.json');

    $documents = [];
    foreach ($files as $file) {
        $doc = readJson($file);
        if ($doc) {
            $documents[] = [
                'id'        => $doc['id'],
                'title'     => $doc['title'],
                'wordCount' => $doc['metadata']['wordCount'] ?? 0,
                'updatedAt' => $doc['updatedAt'],
                'createdAt' => $doc['createdAt'],
            ];
        }
    }

    usort($documents, fn($a, $b) => strcmp($b['updatedAt'], $a['updatedAt']));

    jsonResponse(['documents' => $documents]);
}

/**
 * Create a new writing document.
 *
 * @param  array $user  Current user.
 * @return void
 */
function _handleWritingCreate(array $user): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $missing = validateRequired($input, ['title']);
    if (!empty($missing)) {
        jsonError('Missing required fields: ' . implode(', ', $missing), 422);
    }

    $result = createDocument($user['id'], sanitizeHtml($input['title']));

    if (isset($result['error'])) {
        jsonError($result['error'], 500);
    }

    jsonResponse($result, 201);
}

/**
 * Get a single writing document.
 *
 * @param  array  $user  Current user.
 * @param  string $id    Document ID.
 * @return void
 */
function _handleWritingGet(array $user, string $id): void
{
    if (empty($id)) {
        jsonError('Document ID is required', 400);
    }

    $path = DATA_DIR . '/writing/' . $user['id'] . '/' . $id . '.json';
    $doc  = readJson($path);

    if (!$doc) {
        jsonError('Document not found', 404);
    }

    jsonResponse(['document' => $doc]);
}

/**
 * Save document content.
 *
 * @param  array  $user  Current user.
 * @param  string $id    Document ID.
 * @return void
 */
function _handleWritingSave(array $user, string $id): void
{
    if (empty($id)) {
        jsonError('Document ID is required', 400);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    if (!isset($input['content'])) {
        jsonError('Content is required', 422);
    }

    $result = saveDocument($user['id'], $id, $input['content'], $input['metadata'] ?? []);

    if (isset($result['error'])) {
        jsonError($result['error'], 500);
    }

    jsonResponse(['success' => true, 'document' => $result['document']]);
}

/**
 * Scan a document for writing issues via AI.
 *
 * @param  array  $user  Current user.
 * @param  string $id    Document ID.
 * @return void
 */
function _handleWritingScan(array $user, string $id): void
{
    if (empty($id)) {
        jsonError('Document ID is required', 400);
    }

    $path = DATA_DIR . '/writing/' . $user['id'] . '/' . $id . '.json';
    $doc  = readJson($path);

    if (!$doc) {
        jsonError('Document not found', 404);
    }

    if (empty(trim($doc['content']))) {
        jsonError('Document has no content to scan', 422);
    }

    $prompt = getPromptTemplate('writing_scan', [
        'content'  => $doc['content'],
        'doc_type' => $doc['metadata']['docType'] ?? 'essay',
    ]);

    $messages = [
        ['role' => 'system', 'content' => $prompt['system']],
        ['role' => 'user',   'content' => $prompt['user']],
    ];

    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $result = sendAiRequest(
        'writing_scan',
        $messages,
        $input['model'] ?? null,
        null,
        $user['id']
    );

    if (isset($result['error'])) {
        jsonError($result['error'], 502);
    }

    jsonResponse(['success' => true, 'scan' => $result['data']]);
}

/**
 * Grade a document via AI.
 *
 * @param  array  $user  Current user.
 * @param  string $id    Document ID.
 * @return void
 */
function _handleWritingGrade(array $user, string $id): void
{
    if (empty($id)) {
        jsonError('Document ID is required', 400);
    }

    $path = DATA_DIR . '/writing/' . $user['id'] . '/' . $id . '.json';
    $doc  = readJson($path);

    if (!$doc) {
        jsonError('Document not found', 404);
    }

    if (empty(trim($doc['content']))) {
        jsonError('Document has no content to grade', 422);
    }

    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $prompt = getPromptTemplate('writing_grade', [
        'content'  => $doc['content'],
        'doc_type' => $doc['metadata']['docType'] ?? 'essay',
        'rubric'   => $input['rubric'] ?? null,
    ]);

    $messages = [
        ['role' => 'system', 'content' => $prompt['system']],
        ['role' => 'user',   'content' => $prompt['user']],
    ];

    $result = sendAiRequest(
        'writing_grade',
        $messages,
        $input['model'] ?? null,
        null,
        $user['id']
    );

    if (isset($result['error'])) {
        jsonError($result['error'], 502);
    }

    jsonResponse(['success' => true, 'grade' => $result['data']]);
}

/**
 * Create a manual snapshot of the document.
 *
 * @param  array  $user  Current user.
 * @param  string $id    Document ID.
 * @return void
 */
function _handleWritingSnapshot(array $user, string $id): void
{
    if (empty($id)) {
        jsonError('Document ID is required', 400);
    }

    $path = DATA_DIR . '/writing/' . $user['id'] . '/' . $id . '.json';
    $doc  = readJson($path);

    if (!$doc) {
        jsonError('Document not found', 404);
    }

    $input     = json_decode(file_get_contents('php://input'), true) ?? [];
    $changeType = sanitizeHtml($input['change_type'] ?? 'manual_save');

    $ok = saveSnapshot($user['id'], $id, $doc['content'], $changeType);

    if (!$ok) {
        jsonError('Failed to save snapshot', 500);
    }

    jsonResponse(['success' => true]);
}

/**
 * Get the integrity log for a document.
 *
 * @param  array  $user  Current user.
 * @param  string $id    Document ID.
 * @return void
 */
function _handleWritingIntegrity(array $user, string $id): void
{
    if (empty($id)) {
        jsonError('Document ID is required', 400);
    }

    $log = getIntegrityLog($user['id'], $id);

    jsonResponse(['integrity' => $log]);
}
