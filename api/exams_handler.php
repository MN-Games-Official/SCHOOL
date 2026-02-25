<?php
/**
 * API Handler – Exams
 *
 * Handles exam section generation, listing, retrieval, and section grading.
 *
 * @package School
 */

require_once __DIR__ . '/../includes/ai_gateway.php';
require_once __DIR__ . '/../includes/exam_service.php';

/**
 * Dispatch exams actions.
 *
 * @param  string $action  One of: generate_section, list, get, grade_section.
 * @param  array  $params  Route parameters (may contain 'id').
 * @return void
 */
function handle_exams_handler(string $action, array $params): void
{
    $user = requireAuth();

    switch ($action) {
        case 'generate_section':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonError('Method not allowed', 405);
            }
            requireCsrf();
            _handleExamGenerateSection($user);
            break;

        case 'list':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                jsonError('Method not allowed', 405);
            }
            _handleExamList($user);
            break;

        case 'get':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                jsonError('Method not allowed', 405);
            }
            _handleExamGet($user, $params['id'] ?? '');
            break;

        case 'grade_section':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonError('Method not allowed', 405);
            }
            requireCsrf();
            _handleExamGradeSection($user, $params['id'] ?? '');
            break;

        default:
            jsonError('Unknown exams action', 400);
    }
}

/**
 * Generate a single exam section via AI.
 *
 * @param  array $user  Current user.
 * @return void
 */
function _handleExamGenerateSection(array $user): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $missing = validateRequired($input, ['exam_type', 'section_name']);
    if (!empty($missing)) {
        jsonError('Missing required fields: ' . implode(', ', $missing), 422);
    }

    $examConfig = getExamConfig($input['exam_type']);
    if (!$examConfig) {
        jsonError('Unsupported exam type: ' . $input['exam_type'], 422);
    }

    // Find matching section config
    $sectionConfig = null;
    foreach ($examConfig['sections'] as $sec) {
        if ($sec['name'] === $input['section_name']) {
            $sectionConfig = $sec;
            break;
        }
    }

    $numQuestions = $input['num_questions']
        ?? ($sectionConfig ? $sectionConfig['num_questions'][0] : 20);
    $timeMinutes = $input['time_minutes']
        ?? ($sectionConfig ? (is_array($sectionConfig['time_minutes']) ? $sectionConfig['time_minutes'][0] : $sectionConfig['time_minutes']) : 30);

    $prompt = getPromptTemplate('exam_generate_section', [
        'exam_type'     => $input['exam_type'],
        'section_name'  => $input['section_name'],
        'num_questions'  => (int) $numQuestions,
        'time_minutes'   => (int) $timeMinutes,
        'subject_area'  => $sectionConfig['subject_area'] ?? ($input['subject_area'] ?? ''),
        'passage_based' => $sectionConfig['passage_based'] ?? ($input['passage_based'] ?? false),
    ]);

    $messages = [
        ['role' => 'system', 'content' => $prompt['system']],
        ['role' => 'user',   'content' => $prompt['user']],
    ];

    $result = sendAiRequest(
        'exam_generate_section',
        $messages,
        $input['model'] ?? null,
        isset($input['seed']) ? (int) $input['seed'] : null,
        $user['id']
    );

    if (isset($result['error'])) {
        jsonError($result['error'], 502);
    }

    // Persist the exam
    $examId = $input['exam_id'] ?? generateId();
    $dir    = DATA_DIR . '/exams/' . $user['id'];
    $path   = $dir . '/' . $examId . '.json';
    ensureDir($dir);

    // Load existing exam or create new
    $exam = readJson($path) ?? [
        'id'        => $examId,
        'userId'    => $user['id'],
        'examType'  => $input['exam_type'],
        'sections'  => [],
        'results'   => [],
        'createdAt' => gmdate('c'),
    ];

    // Append section
    $exam['sections'][] = [
        'name'       => $input['section_name'],
        'data'       => $result['data'],
        'generatedAt'=> gmdate('c'),
    ];

    writeJson($path, $exam);

    jsonResponse(['success' => true, 'exam' => $exam], 201);
}

/**
 * List all exams for the current user.
 *
 * @param  array $user  Current user.
 * @return void
 */
function _handleExamList(array $user): void
{
    $dir   = DATA_DIR . '/exams/' . $user['id'];
    $files = listFiles($dir, '*.json');

    $exams = [];
    foreach ($files as $file) {
        $exam = readJson($file);
        if ($exam) {
            $exams[] = [
                'id'           => $exam['id'],
                'examType'     => $exam['examType'],
                'numSections'  => count($exam['sections'] ?? []),
                'hasResults'   => !empty($exam['results']),
                'createdAt'    => $exam['createdAt'],
            ];
        }
    }

    usort($exams, fn($a, $b) => strcmp($b['createdAt'], $a['createdAt']));

    jsonResponse(['exams' => $exams]);
}

/**
 * Get a single exam by ID.
 *
 * @param  array  $user  Current user.
 * @param  string $id    Exam ID.
 * @return void
 */
function _handleExamGet(array $user, string $id): void
{
    if (empty($id)) {
        jsonError('Exam ID is required', 400);
    }

    $path = DATA_DIR . '/exams/' . $user['id'] . '/' . $id . '.json';
    $exam = readJson($path);

    if (!$exam) {
        jsonError('Exam not found', 404);
    }

    jsonResponse(['exam' => $exam]);
}

/**
 * Grade a single section of an exam.
 *
 * @param  array  $user  Current user.
 * @param  string $id    Exam ID.
 * @return void
 */
function _handleExamGradeSection(array $user, string $id): void
{
    if (empty($id)) {
        jsonError('Exam ID is required', 400);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $missing = validateRequired($input, ['section_index', 'answers']);
    if (!empty($missing)) {
        jsonError('Missing required fields: ' . implode(', ', $missing), 422);
    }

    $path = DATA_DIR . '/exams/' . $user['id'] . '/' . $id . '.json';
    $exam = readJson($path);

    if (!$exam) {
        jsonError('Exam not found', 404);
    }

    $sectionIdx = (int) $input['section_index'];
    if (!isset($exam['sections'][$sectionIdx])) {
        jsonError('Section not found', 404);
    }

    $section   = $exam['sections'][$sectionIdx];
    $questions = $section['data']['questions'] ?? [];
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
            'questionId' => $qId,
            'userAnswer' => $userAnswer,
            'correct'    => $q['correct'],
            'isCorrect'  => $isCorrect,
        ];
    }

    // Estimate scaled score
    $scaledScore = estimateScaledScore($exam['examType'], $correct, $total);

    $sectionResult = [
        'sectionIndex' => $sectionIdx,
        'sectionName'  => $section['name'],
        'score'        => $total > 0 ? round(($correct / $total) * 100, 1) : 0,
        'correct'      => $correct,
        'total'        => $total,
        'scaledScore'  => $scaledScore,
        'details'      => $details,
        'gradedAt'     => gmdate('c'),
    ];

    $exam['results'][$sectionIdx] = $sectionResult;
    writeJson($path, $exam);

    jsonResponse(['success' => true, 'result' => $sectionResult]);
}
