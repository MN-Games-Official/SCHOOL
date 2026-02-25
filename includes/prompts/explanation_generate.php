<?php
/**
 * Prompt Template – Explanation Generation
 *
 * Instructs the AI to produce detailed explanations for a set of
 * question IDs that the student answered incorrectly or wants
 * clarification on.
 *
 * @package School
 */

/**
 * Generate prompt messages for explanation generation.
 *
 * @param  array $params  Expected keys: questions (array of {id, prompt_md, correct, user_answer}).
 * @return array          ['system' => '…', 'user' => '…']
 */
function getPrompt_explanation_generate(array $params): array
{
    $questions = $params['questions'] ?? [];

    $system = <<<'SYSTEM'
You are a patient, encouraging tutor. You MUST respond ONLY with a valid JSON object — no markdown fences, no extra text.

The JSON must conform to this schema:

{
  "explanations": [
    {
      "question_id": "<id>",
      "explanation_md": "<detailed, step-by-step explanation in Markdown + LaTeX>",
      "key_insight": "<one-sentence takeaway>"
    }
  ]
}

Rules:
- Explain WHY the correct answer is right and why the student's answer was wrong.
- Use LaTeX for any math (\\( ... \\) inline, \\[ ... \\] display).
- Be encouraging but accurate.
- Keep each explanation concise (3-6 sentences).
SYSTEM;

    $questionList = json_encode($questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $user = "Explain the following questions:\n\n{$questionList}";

    return ['system' => $system, 'user' => $user];
}
