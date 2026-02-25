<?php
/**
 * Cerebras AI Gateway
 *
 * Manages all communication with the Cerebras AI API, including request
 * building, response parsing/validation, prompt template loading, model
 * selection, and request logging.
 *
 * @package School
 */

/**
 * Send a chat-completion request to the Cerebras API.
 *
 * @param  string      $operation  Operation identifier (e.g. "lesson_generate").
 * @param  array       $messages   Chat messages [{role, content}, ...].
 * @param  string|null $model      Model override (null = use default for operation).
 * @param  int|null    $seed       Deterministic seed (null = random).
 * @param  string|null $userId     ID of the requesting user (for logging).
 * @return array                   ['success' => true, 'data' => ...] or ['error' => '...']
 */
function sendAiRequest(
    string  $operation,
    array   $messages,
    ?string $model = null,
    ?int    $seed = null,
    ?string $userId = null
): array {
    $apiKey = CEREBRAS_API_KEY;
    if (empty($apiKey)) {
        return ['error' => 'Cerebras API key is not configured.'];
    }

    $model = $model ?: getDefaultModel($operation);
    $seed  = $seed  ?? random_int(1, 999999);

    // ── Build payload ────────────────────────────────────────────────────────
    $payload = [
        'model'       => $model,
        'messages'    => $messages,
        'temperature' => 0,
        'top_p'       => 1,
        'seed'        => $seed,
        'max_tokens'  => -1,
        'stream'      => false,
    ];

    // ── cURL request ─────────────────────────────────────────────────────────
    $ch = curl_init('https://api.cerebras.ai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
    ]);

    $rawResponse = curl_exec($ch);
    $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError   = curl_error($ch);
    curl_close($ch);

    if ($rawResponse === false) {
        error_log("sendAiRequest: cURL error – {$curlError}");
        return ['error' => 'Failed to contact AI service.'];
    }

    // ── Parse API response ───────────────────────────────────────────────────
    $apiResponse = json_decode($rawResponse, true);
    if (!$apiResponse) {
        error_log("sendAiRequest: non-JSON response (HTTP {$httpCode})");
        return ['error' => 'Invalid response from AI service.'];
    }

    if ($httpCode !== 200) {
        $msg = $apiResponse['error']['message'] ?? 'Unknown API error';
        error_log("sendAiRequest: API error HTTP {$httpCode} – {$msg}");
        return ['error' => "AI service error: {$msg}"];
    }

    // ── Extract content ──────────────────────────────────────────────────────
    $content = $apiResponse['choices'][0]['message']['content'] ?? null;
    if ($content === null) {
        return ['error' => 'AI response contained no content.'];
    }

    // ── Try to parse content as JSON ─────────────────────────────────────────
    $parsed = _tryParseJsonContent($content);

    // ── Validate against operation schema ────────────────────────────────────
    if ($parsed !== null) {
        $errors = validateAiResponse($parsed, $operation);
        if (!empty($errors)) {
            error_log("sendAiRequest: schema validation failed – " . implode('; ', $errors));
            // Return the raw parsed data anyway, but flag the errors
            $parsed['_validation_warnings'] = $errors;
        }
    }

    // ── Log ──────────────────────────────────────────────────────────────────
    logAiRequest([
        'operation'     => $operation,
        'model'         => $model,
        'seed'          => $seed,
        'timestamp'     => gmdate('c'),
        'response_hash' => hash('sha256', $content),
        'user_id'       => $userId,
        'http_code'     => $httpCode,
    ]);

    return [
        'success' => true,
        'data'    => $parsed ?? $content,
        'raw'     => $content,
    ];
}

/**
 * Load a prompt template for a given operation.
 *
 * Template files live in /includes/prompts/{operation}.php and expose a
 * function named getPrompt_{operation}(array $params): array  that returns
 * ['system' => '…', 'user' => '…'].
 *
 * @param  string $operation  Operation name.
 * @param  array  $params     Parameters to pass to the template function.
 * @return array              ['system' => '…', 'user' => '…']
 */
function getPromptTemplate(string $operation, array $params = []): array
{
    $file = __DIR__ . '/prompts/' . $operation . '.php';
    if (!file_exists($file)) {
        return ['system' => '', 'user' => ''];
    }

    require_once $file;

    $fn = 'getPrompt_' . $operation;
    if (function_exists($fn)) {
        return $fn($params);
    }

    return ['system' => '', 'user' => ''];
}

/**
 * Return the recommended default model for an operation.
 *
 * @param  string $operation  Operation identifier.
 * @return string             Model name.
 */
function getDefaultModel(string $operation): string
{
    $models = [
        'lesson_generate'       => 'gpt-oss-120b',
        'quiz_generate'         => 'gpt-oss-120b',
        'exam_generate_section' => 'gpt-oss-120b',
        'explanation_generate'  => 'llama3.1-8b',
        'hint_generate'         => 'llama3.1-8b',
        'writing_scan'          => 'llama3.1-8b',
        'writing_grade'         => 'gpt-oss-120b',
    ];

    return $models[$operation] ?? 'llama3.1-8b';
}

/**
 * Persist an AI request log entry to disk.
 *
 * @param  array $logData  Associative log entry.
 * @return bool            True on success.
 */
function logAiRequest(array $logData): bool
{
    $logDir  = DATA_DIR . '/ai_logs';
    $logFile = $logDir . '/' . date('Y-m-d') . '.json';

    return appendJson($logFile, $logData);
}

// ── Internal Helpers ─────────────────────────────────────────────────────────

/**
 * Attempt to extract and parse JSON from AI content.
 *
 * Handles both raw JSON and markdown-fenced JSON blocks.
 *
 * @param  string $content  Raw AI response content.
 * @return array|null       Decoded array or null if not valid JSON.
 */
function _tryParseJsonContent(string $content): ?array
{
    // Direct parse
    $data = json_decode(trim($content), true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
        return $data;
    }

    // Try to extract from markdown code fence
    if (preg_match('/```(?:json)?\s*\n?(.*?)\n?\s*```/s', $content, $m)) {
        $data = json_decode(trim($m[1]), true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }
    }

    return null;
}
