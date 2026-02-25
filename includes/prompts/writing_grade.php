<?php
/**
 * Prompt Template – Writing Grade
 *
 * Instructs the AI to grade a document against a rubric, returning
 * scores and feedback.
 *
 * @package School
 */

/**
 * Generate prompt messages for writing grading.
 *
 * @param  array $params  Expected keys: content (string), rubric (optional array), doc_type (optional).
 * @return array          ['system' => '…', 'user' => '…']
 */
function getPrompt_writing_grade(array $params): array
{
    $content = $params['content']  ?? '';
    $docType = $params['doc_type'] ?? 'essay';
    $rubric  = $params['rubric']   ?? null;

    $rubricJson = '';
    if ($rubric) {
        $rubricJson = "\n\nUse this custom rubric:\n" . json_encode($rubric, JSON_PRETTY_PRINT);
    }

    $system = <<<SYSTEM
You are an experienced writing instructor grading a student's work. You MUST respond ONLY with a valid JSON object — no markdown fences, no extra text.

The JSON must conform to this schema:

{
  "overall_score": <float 0.0 to 100.0>,
  "letter_grade": "<A+ through F>",
  "rubric_scores": {
    "thesis_clarity": <float 0-100>,
    "organization": <float 0-100>,
    "evidence_support": <float 0-100>,
    "language_style": <float 0-100>,
    "grammar_mechanics": <float 0-100>,
    "critical_thinking": <float 0-100>
  },
  "feedback_md": "<detailed Markdown feedback covering strengths and areas for improvement>",
  "strengths": ["<strength1>", "<strength2>"],
  "improvements": ["<area1>", "<area2>"]
}

Rules:
- Be fair, specific, and constructive.
- Justify each rubric score with a brief note in the feedback.
- overall_score should be a weighted average of rubric scores.
- Provide actionable advice in the improvements array.{$rubricJson}
SYSTEM;

    $user = "Grade this {$docType}:\n\n{$content}";

    return ['system' => $system, 'user' => $user];
}
