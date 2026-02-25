<?php
/**
 * API Handler – AI Helpers
 *
 * Handles generic AI requests: explanations, hints, and free-form prompts.
 *
 * @package School
 */

require_once __DIR__ . '/../includes/ai_gateway.php';

/**
 * Dispatch AI helper actions.
 *
 * @param  string $action  One of: request, explain, hint.
 * @param  array  $params  Route parameters (unused).
 * @return void
 */
function handle_ai_handler(string $action, array $params): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonError('Method not allowed', 405);
    }

    requireCsrf();
    $user = requireAuth();

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    switch ($action) {
        case 'request':
            _handleAiRequest($user, $input);
            break;
        case 'explain':
            _handleAiExplain($user, $input);
            break;
        case 'hint':
            _handleAiHint($user, $input);
            break;
        default:
            jsonError('Unknown AI action', 400);
    }
}

/**
 * Handle a generic AI request with custom messages.
 *
 * @param  array $user   Current user.
 * @param  array $input  Request body.
 * @return void
 */
function _handleAiRequest(array $user, array $input): void
{
    if (empty($input['messages']) || !is_array($input['messages'])) {
        jsonError('Messages array is required', 422);
    }

    $operation = sanitizeHtml($input['operation'] ?? 'generic');

    $result = sendAiRequest(
        $operation,
        $input['messages'],
        $input['model'] ?? null,
        isset($input['seed']) ? (int) $input['seed'] : null,
        $user['id']
    );

    if (isset($result['error'])) {
        jsonError($result['error'], 502);
    }

    jsonResponse(['success' => true, 'data' => $result['data']]);
}

/**
 * Generate explanations for a set of questions.
 *
 * @param  array $user   Current user.
 * @param  array $input  Request body with 'questions' array.
 * @return void
 */
function _handleAiExplain(array $user, array $input): void
{
    if (empty($input['questions']) || !is_array($input['questions'])) {
        jsonError('Questions array is required', 422);
    }

    $prompt = getPromptTemplate('explanation_generate', [
        'questions' => $input['questions'],
    ]);

    $messages = [
        ['role' => 'system', 'content' => $prompt['system']],
        ['role' => 'user',   'content' => $prompt['user']],
    ];

    $result = sendAiRequest(
        'explanation_generate',
        $messages,
        $input['model'] ?? null,
        isset($input['seed']) ? (int) $input['seed'] : null,
        $user['id']
    );

    if (isset($result['error'])) {
        jsonError($result['error'], 502);
    }

    jsonResponse(['success' => true, 'data' => $result['data']]);
}

/**
 * Generate a hint for a single question.
 *
 * @param  array $user   Current user.
 * @param  array $input  Request body with 'question' object.
 * @return void
 */
function _handleAiHint(array $user, array $input): void
{
    if (empty($input['question'])) {
        jsonError('Question object is required', 422);
    }

    $prompt = getPromptTemplate('hint_generate', [
        'question' => $input['question'],
    ]);

    $messages = [
        ['role' => 'system', 'content' => $prompt['system']],
        ['role' => 'user',   'content' => $prompt['user']],
    ];

    $result = sendAiRequest(
        'hint_generate',
        $messages,
        $input['model'] ?? null,
        isset($input['seed']) ? (int) $input['seed'] : null,
        $user['id']
    );

    if (isset($result['error'])) {
        jsonError($result['error'], 502);
    }

    jsonResponse(['success' => true, 'data' => $result['data']]);
}
