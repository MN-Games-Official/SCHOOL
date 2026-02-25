<?php
/**
 * API Handler – Lessons
 *
 * Handles lesson generation (POST), listing (GET), and retrieval (GET).
 *
 * @package School
 */

require_once __DIR__ . '/../includes/ai_gateway.php';

/**
 * Dispatch lessons actions.
 *
 * @param  string $action  One of: generate, list, get.
 * @param  array  $params  Route parameters (may contain 'id').
 * @return void
 */
function handle_lessons_handler(string $action, array $params): void
{
    $user = requireAuth();

    switch ($action) {
        case 'generate':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonError('Method not allowed', 405);
            }
            requireCsrf();
            _handleLessonGenerate($user);
            break;

        case 'list':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                jsonError('Method not allowed', 405);
            }
            _handleLessonList($user);
            break;

        case 'get':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                jsonError('Method not allowed', 405);
            }
            _handleLessonGet($user, $params['id'] ?? '');
            break;

        default:
            jsonError('Unknown lessons action', 400);
    }
}

/**
 * Generate a new lesson via AI.
 *
 * @param  array $user  Current user.
 * @return void
 */
function _handleLessonGenerate(array $user): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $missing = validateRequired($input, ['topic']);
    if (!empty($missing)) {
        jsonError('Missing required fields: ' . implode(', ', $missing), 422);
    }

    // Build prompt
    $prompt = getPromptTemplate('lesson_generate', [
        'topic'    => sanitizeHtml($input['topic']),
        'subtopic' => sanitizeHtml($input['subtopic'] ?? ''),
        'level'    => sanitizeHtml($input['level'] ?? 'intermediate'),
    ]);

    $messages = [
        ['role' => 'system', 'content' => $prompt['system']],
        ['role' => 'user',   'content' => $prompt['user']],
    ];

    $result = sendAiRequest(
        'lesson_generate',
        $messages,
        $input['model'] ?? null,
        isset($input['seed']) ? (int) $input['seed'] : null,
        $user['id']
    );

    if (isset($result['error'])) {
        jsonError($result['error'], 502);
    }

    // Persist the lesson
    $lessonId = generateId();
    $lesson   = [
        'id'        => $lessonId,
        'userId'    => $user['id'],
        'topic'     => $input['topic'],
        'subtopic'  => $input['subtopic'] ?? '',
        'level'     => $input['level'] ?? 'intermediate',
        'data'      => $result['data'],
        'createdAt' => gmdate('c'),
    ];

    $dir = DATA_DIR . '/lessons/' . $user['id'];
    ensureDir($dir);
    writeJson($dir . '/' . $lessonId . '.json', $lesson);

    jsonResponse(['success' => true, 'lesson' => $lesson], 201);
}

/**
 * List all lessons for the current user.
 *
 * @param  array $user  Current user.
 * @return void
 */
function _handleLessonList(array $user): void
{
    $dir   = DATA_DIR . '/lessons/' . $user['id'];
    $files = listFiles($dir, '*.json');

    $lessons = [];
    foreach ($files as $file) {
        $lesson = readJson($file);
        if ($lesson) {
            // Return summary (no full data)
            $lessons[] = [
                'id'        => $lesson['id'],
                'topic'     => $lesson['topic'],
                'subtopic'  => $lesson['subtopic'] ?? '',
                'level'     => $lesson['level'] ?? '',
                'title'     => $lesson['data']['title'] ?? $lesson['topic'],
                'createdAt' => $lesson['createdAt'],
            ];
        }
    }

    // Sort by newest first
    usort($lessons, fn($a, $b) => strcmp($b['createdAt'], $a['createdAt']));

    jsonResponse(['lessons' => $lessons]);
}

/**
 * Get a single lesson by ID.
 *
 * @param  array  $user  Current user.
 * @param  string $id    Lesson ID.
 * @return void
 */
function _handleLessonGet(array $user, string $id): void
{
    if (empty($id)) {
        jsonError('Lesson ID is required', 400);
    }

    $path   = DATA_DIR . '/lessons/' . $user['id'] . '/' . $id . '.json';
    $lesson = readJson($path);

    if (!$lesson) {
        jsonError('Lesson not found', 404);
    }

    jsonResponse(['lesson' => $lesson]);
}
