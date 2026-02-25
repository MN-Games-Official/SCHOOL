<?php
/**
 * Prompt Template – Hint Generation
 *
 * Instructs the AI to produce a single helpful hint for one question
 * without revealing the answer.
 *
 * @package School
 */

/**
 * Generate prompt messages for hint generation.
 *
 * @param  array $params  Expected keys: question (object with id, prompt_md, options, type).
 * @return array          ['system' => '…', 'user' => '…']
 */
function getPrompt_hint_generate(array $params): array
{
    $question = $params['question'] ?? [];

    $system = <<<'SYSTEM'
You are a supportive tutor giving a helpful hint. You MUST respond ONLY with a valid JSON object — no markdown fences, no extra text.

The JSON must conform to this schema:

{
  "hint": "<a helpful hint in Markdown + LaTeX that nudges the student toward the answer WITHOUT revealing it>"
}

Rules:
- Do NOT reveal the correct answer.
- Guide the student's thinking with a useful strategy or observation.
- Use LaTeX for any math (\\( ... \\) inline, \\[ ... \\] display).
- Keep the hint to 1-3 sentences.
SYSTEM;

    $questionJson = json_encode($question, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $user = "Give a hint for this question:\n\n{$questionJson}";

    return ['system' => $system, 'user' => $user];
}
