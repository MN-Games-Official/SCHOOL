<?php
/**
 * Prompt Template – Quiz Generation
 *
 * Builds system and user messages that instruct the AI to produce a
 * set of quiz questions as a structured JSON object.
 *
 * @package School
 */

/**
 * Generate prompt messages for quiz creation.
 *
 * @param  array $params  Expected keys: topic, num_questions, difficulty, subtopic (optional).
 * @return array          ['system' => '…', 'user' => '…']
 */
function getPrompt_quiz_generate(array $params): array
{
    $topic        = $params['topic']         ?? 'General';
    $subtopic     = $params['subtopic']      ?? '';
    $numQuestions = $params['num_questions']  ?? 10;
    $difficulty   = $params['difficulty']     ?? 'mixed';

    $system = <<<'SYSTEM'
You are an expert test-item writer. You MUST respond ONLY with a valid JSON object — no markdown fences, no explanations, no extra text.

The JSON must conform to this schema:

{
  "schema_version": "1.0",
  "topic": "<topic>",
  "questions": [
    {
      "id": "<unique question id, e.g. q1, q2>",
      "type": "multiple_choice | true_false | short_answer",
      "prompt_md": "<question text in Markdown + LaTeX>",
      "options": ["A) ...", "B) ...", "C) ...", "D) ..."],
      "correct": "<correct option letter or value>",
      "difficulty": "easy | medium | hard",
      "explanation_md": "<brief explanation of the correct answer>",
      "tags": ["<tag1>", "<tag2>"]
    }
  ]
}

Rules:
- For multiple_choice questions, always provide exactly 4 options labeled A-D.
- For true_false, options should be ["True", "False"].
- For short_answer, options should be an empty array [].
- Distribute difficulty levels across questions when difficulty is "mixed".
- Use LaTeX (\\( ... \\) inline, \\[ ... \\] display) for any mathematical notation.
- Each question must have a clear, unambiguous correct answer.
- explanation_md should be concise but educational.
SYSTEM;

    $subtopicClause = $subtopic ? " with a focus on '{$subtopic}'" : '';
    $user = "Generate {$numQuestions} quiz questions on '{$topic}'{$subtopicClause} at {$difficulty} difficulty.";

    return ['system' => $system, 'user' => $user];
}
