<?php
/**
 * API Handler – Quizzes
 *
 * Handles quiz generation, listing, retrieval, and grading.
 *
 * @package School
 */

require_once __DIR__ . '/../includes/ai_gateway.php';

/**
 * Dispatch quizzes actions.
 *
 * @param  string $action  One of: generate, list, get, grade.
 * @param  array  $params  Route parameters (may contain 'id').
 * @return void
 */
function handle_quizzes_handler(string $action, array $params): void
{
    $user = requireAuth();

    switch ($action) {
        case 'generate':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonError('Method not allowed', 405);
            }
            requireCsrf();
            _handleQuizGenerate($user);
            break;

        case 'list':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                jsonError('Method not allowed', 405);
            }
            _handleQuizList($user);
            break;

        case 'get':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                jsonError('Method not allowed', 405);
            }
            _handleQuizGet($user, $params['id'] ?? '');
            break;

        case 'grade':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonError('Method not allowed', 405);
            }
            requireCsrf();
            _handleQuizGrade($user, $params['id'] ?? '');
            break;

        default:
            jsonError('Unknown quizzes action', 400);
    }
}

/**
 * Generate a new quiz via AI.
 *
 * @param  array $user  Current user.
 * @return void
 */
function _handleQuizGenerate(array $user): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $missing = validateRequired($input, ['topic']);
    if (!empty($missing)) {
        jsonError('Missing required fields: ' . implode(', ', $missing), 422);
    }

    $prompt = getPromptTemplate('quiz_generate', [
        'topic'         => sanitizeHtml($input['topic']),
        'subtopic'      => sanitizeHtml($input['subtopic'] ?? ''),
        'num_questions'  => (int) ($input['num_questions'] ?? 10),
        'difficulty'    => sanitizeHtml($input['difficulty'] ?? 'mixed'),
    ]);

    $messages = [
        ['role' => 'system', 'content' => $prompt['system']],
        ['role' => 'user',   'content' => $prompt['user']],
    ];

    $result = sendAiRequest(
        'quiz_generate',
        $messages,
        $input['model'] ?? null,
        isset($input['seed']) ? (int) $input['seed'] : null,
        $user['id']
    );

    if (isset($result['error'])) {
        jsonError($result['error'], 502);
    }

    // Persist the quiz
    $quizId = generateId();
    $quiz   = [
        'id'         => $quizId,
        'userId'     => $user['id'],
        'topic'      => $input['topic'],
        'subtopic'   => $input['subtopic'] ?? '',
        'difficulty' => $input['difficulty'] ?? 'mixed',
        'data'       => $result['data'],
        'results'    => null,
        'createdAt'  => gmdate('c'),
    ];

    $dir = DATA_DIR . '/quizzes/' . $user['id'];
    ensureDir($dir);
    writeJson($dir . '/' . $quizId . '.json', $quiz);

    jsonResponse(['success' => true, 'quiz' => $quiz], 201);
}

/**
 * List all quizzes for the current user.
 *
 * @param  array $user  Current user.
 * @return void
 */
function _handleQuizList(array $user): void
{
    $dir   = DATA_DIR . '/quizzes/' . $user['id'];
    $files = listFiles($dir, '*.json');

    $quizzes = [];
    foreach ($files as $file) {
        $quiz = readJson($file);
        if ($quiz) {
            $quizzes[] = [
                'id'         => $quiz['id'],
                'topic'      => $quiz['topic'],
                'subtopic'   => $quiz['subtopic'] ?? '',
                'difficulty' => $quiz['difficulty'] ?? 'mixed',
                'numQuestions'=> is_array($quiz['data']['questions'] ?? null) ? count($quiz['data']['questions']) : 0,
                'hasResults' => $quiz['results'] !== null,
                'createdAt'  => $quiz['createdAt'],
            ];
        }
    }

    usort($quizzes, fn($a, $b) => strcmp($b['createdAt'], $a['createdAt']));

    jsonResponse(['quizzes' => $quizzes]);
}

/**
 * Get a single quiz by ID.
 *
 * @param  array  $user  Current user.
 * @param  string $id    Quiz ID.
 * @return void
 */
function _handleQuizGet(array $user, string $id): void
{
    if (empty($id)) {
        jsonError('Quiz ID is required', 400);
    }

    $path = DATA_DIR . '/quizzes/' . $user['id'] . '/' . $id . '.json';
    $quiz = readJson($path);

    if (!$quiz) {
        jsonError('Quiz not found', 404);
    }

    jsonResponse(['quiz' => $quiz]);
}

/**
 * Grade a quiz submission.
 *
 * Accepts an answers map {questionId: userAnswer} and grades locally.
 *
 * @param  array  $user  Current user.
 * @param  string $id    Quiz ID.
 * @return void
 */
function _handleQuizGrade(array $user, string $id): void
{
    if (empty($id)) {
        jsonError('Quiz ID is required', 400);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    if (empty($input['answers']) || !is_array($input['answers'])) {
        jsonError('Answers map is required', 422);
    }

    $path = DATA_DIR . '/quizzes/' . $user['id'] . '/' . $id . '.json';
    $quiz = readJson($path);

    if (!$quiz) {
        jsonError('Quiz not found', 404);
    }

    $questions = $quiz['data']['questions'] ?? [];
    $answers   = $input['answers'];
    $correct   = 0;
    $total     = count($questions);
    $details   = [];

    foreach ($questions as $q) {
        $qId        = $q['id'];
        $userAnswer = $answers[$qId] ?? null;
        $isCorrect  = $userAnswer !== null
            && strtolower(trim((string) $userAnswer)) === strtolower(trim((string) $q['correct']));

        if ($isCorrect) {
            $correct++;
        }

        $details[] = [
            'questionId'  => $qId,
            'userAnswer'  => $userAnswer,
            'correct'     => $q['correct'],
            'isCorrect'   => $isCorrect,
        ];
    }

    $results = [
        'score'      => $total > 0 ? round(($correct / $total) * 100, 1) : 0,
        'correct'    => $correct,
        'total'      => $total,
        'details'    => $details,
        'gradedAt'   => gmdate('c'),
    ];

    // Persist results
    $quiz['results'] = $results;
    writeJson($path, $quiz);

    jsonResponse(['success' => true, 'results' => $results]);
}
