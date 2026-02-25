<?php
/**
 * Prompt Template – Lesson Generation
 *
 * Builds system and user messages that instruct the AI to produce a
 * structured lesson in JSON format with Markdown + LaTeX content.
 *
 * @package School
 */

/**
 * Generate prompt messages for lesson creation.
 *
 * @param  array $params  Expected keys: topic, subtopic (optional), level (optional).
 * @return array          ['system' => '…', 'user' => '…']
 */
function getPrompt_lesson_generate(array $params): array
{
    $topic    = $params['topic']    ?? 'General';
    $subtopic = $params['subtopic'] ?? '';
    $level    = $params['level']    ?? 'intermediate';

    $system = <<<'SYSTEM'
You are an expert educational content creator. You MUST respond ONLY with a valid JSON object — no markdown fences, no explanations, no extra text.

The JSON must conform to this schema:

{
  "schema_version": "1.0",
  "title": "<lesson title>",
  "topic": "<topic>",
  "subtopic": "<subtopic or empty string>",
  "level": "<beginner|intermediate|advanced>",
  "content_md": "<full lesson in Markdown + LaTeX>",
  "key_concepts": ["<concept1>", "<concept2>", ...],
  "estimated_minutes": <integer>
}

Rules for content_md:
- Use proper Markdown headings (##, ###).
- Structure the lesson with these sections:
  1. **Overview** – brief introduction to the topic.
  2. **Key Concepts** – definitions and core ideas.
  3. **Worked Examples** – step-by-step solutions using LaTeX for math (delimit inline with \( ... \) and display with \[ ... \]).
  4. **Common Mistakes** – pitfalls students should avoid.
  5. **Quick Practice** – 2-3 practice problems (answers at the end).
- Be thorough yet concise; target the specified difficulty level.
- Include LaTeX for any mathematical notation.
SYSTEM;

    $subtopicClause = $subtopic ? " focusing on '{$subtopic}'" : '';
    $user = "Create a {$level}-level lesson on '{$topic}'{$subtopicClause}.";

    return ['system' => $system, 'user' => $user];
}
