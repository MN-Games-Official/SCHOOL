<?php
/**
 * Input Validation Helpers
 *
 * Provides reusable validation and sanitisation functions for user input,
 * JSON schemas, and AI response structures.
 *
 * @package School
 */

/**
 * Check that all required fields are present and non-empty in the data array.
 *
 * @param  array $data   Associative input array.
 * @param  array $fields List of required field names.
 * @return array         Array of missing field names (empty when valid).
 */
function validateRequired(array $data, array $fields): array
{
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            $missing[] = $field;
        }
    }
    return $missing;
}

/**
 * Validate that a string length is within bounds.
 *
 * @param  string $str  The string to check.
 * @param  int    $min  Minimum length (inclusive).
 * @param  int    $max  Maximum length (inclusive).
 * @return bool         True if the string length is within [min, max].
 */
function validateLength(string $str, int $min, int $max): bool
{
    $len = mb_strlen($str, 'UTF-8');
    return $len >= $min && $len <= $max;
}

/**
 * Strip dangerous HTML while preserving safe content.
 *
 * @param  string $str  Raw input string.
 * @return string       Sanitised string.
 */
function sanitizeHtml(string $str): string
{
    // Remove null bytes
    $str = str_replace("\0", '', $str);
    // Strip all HTML tags
    $str = strip_tags($str);
    // Encode remaining special characters
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Lightweight JSON-schema validation.
 *
 * Validates that $data conforms to a simplified schema definition.
 * Schema format:
 *   [
 *       'field_name' => ['type' => 'string|int|float|bool|array|object', 'required' => true],
 *       'nested'     => ['type' => 'object', 'properties' => [ ... ]],
 *   ]
 *
 * @param  array $data   Data to validate.
 * @param  array $schema Schema definition.
 * @return array         Array of error messages (empty when valid).
 */
function validateJsonSchema(array $data, array $schema): array
{
    $errors = [];

    foreach ($schema as $field => $rules) {
        $required = $rules['required'] ?? false;
        $type     = $rules['type']     ?? 'string';

        // Required check
        if ($required && !array_key_exists($field, $data)) {
            $errors[] = "Missing required field: {$field}";
            continue;
        }

        // Skip optional missing fields
        if (!array_key_exists($field, $data)) {
            continue;
        }

        $value = $data[$field];

        // Type check
        $valid = match ($type) {
            'string'  => is_string($value),
            'int'     => is_int($value),
            'float'   => is_float($value) || is_int($value),
            'bool'    => is_bool($value),
            'array'   => is_array($value) && array_is_list($value),
            'object'  => is_array($value) && !array_is_list($value),
            default   => true,
        };

        if (!$valid) {
            $errors[] = "Field '{$field}' must be of type {$type}";
        }

        // Recurse into object properties
        if ($type === 'object' && isset($rules['properties']) && is_array($value)) {
            $nested = validateJsonSchema($value, $rules['properties']);
            foreach ($nested as $err) {
                $errors[] = "{$field}.{$err}";
            }
        }
    }

    return $errors;
}

/**
 * Validate an AI response JSON structure for a given operation.
 *
 * Each operation expects specific top-level keys to be present.
 *
 * @param  array  $response  Decoded AI response.
 * @param  string $operation Operation identifier (e.g. "lesson_generate").
 * @return array             Array of error messages (empty when valid).
 */
function validateAiResponse(array $response, string $operation): array
{
    $schemas = [
        'lesson_generate' => [
            'schema_version' => ['type' => 'string',  'required' => true],
            'title'          => ['type' => 'string',  'required' => true],
            'content_md'     => ['type' => 'string',  'required' => true],
        ],
        'quiz_generate' => [
            'schema_version' => ['type' => 'string',  'required' => true],
            'questions'      => ['type' => 'array',   'required' => true],
        ],
        'exam_generate_section' => [
            'schema_version' => ['type' => 'string',  'required' => true],
            'questions'      => ['type' => 'array',   'required' => true],
        ],
        'explanation_generate' => [
            'explanations' => ['type' => 'array', 'required' => true],
        ],
        'hint_generate' => [
            'hint' => ['type' => 'string', 'required' => true],
        ],
        'writing_scan' => [
            'issues' => ['type' => 'array', 'required' => true],
        ],
        'writing_grade' => [
            'overall_score' => ['type' => 'float', 'required' => true],
            'rubric_scores' => ['type' => 'object', 'required' => true],
        ],
    ];

    if (!isset($schemas[$operation])) {
        return ["Unknown operation: {$operation}"];
    }

    return validateJsonSchema($response, $schemas[$operation]);
}
