<?php
/**
 * Prompt Template – Exam Section Generation
 *
 * Builds system and user messages that instruct the AI to produce a
 * single exam section with support for passage-based question groups
 * and multiple question types.
 *
 * @package School
 */

/**
 * Generate prompt messages for exam section creation.
 *
 * @param  array $params  Expected keys: exam_type, section_name, num_questions,
 *                        time_minutes, subject_area, passage_based (bool).
 * @return array          ['system' => '…', 'user' => '…']
 */
function getPrompt_exam_generate_section(array $params): array
{
    $examType     = $params['exam_type']      ?? 'ACT';
    $sectionName  = $params['section_name']   ?? 'Section';
    $numQuestions = $params['num_questions']   ?? 20;
    $timeMinutes  = $params['time_minutes']   ?? 30;
    $subjectArea  = $params['subject_area']   ?? '';
    $passageBased = $params['passage_based']  ?? false;

    $passageInstructions = '';
    if ($passageBased) {
        $passageInstructions = <<<'PASSAGE'

When passage_based is true, group questions under passages:
- Each group has a "passage_md" field with the reading passage in Markdown.
- Questions in the group reference that passage.
- A typical group contains 5-10 questions.
PASSAGE;
    }

    $system = <<<SYSTEM
You are an expert standardised-test item writer specialising in {$examType} exams. You MUST respond ONLY with a valid JSON object — no markdown fences, no explanations, no extra text.

The JSON must conform to this schema:

{
  "schema_version": "1.0",
  "exam_type": "{$examType}",
  "section_name": "<section name>",
  "time_minutes": <integer>,
  "questions": [
    {
      "id": "<unique id>",
      "type": "multiple_choice | grid_in | short_answer",
      "group_id": "<passage group id or null>",
      "passage_md": "<passage text if first question in group, else null>",
      "prompt_md": "<question text in Markdown + LaTeX>",
      "options": ["A) ...", "B) ...", "C) ...", "D) ..."],
      "correct": "<correct answer>",
      "difficulty": "easy | medium | hard",
      "explanation_md": "<brief explanation>",
      "tags": ["<tag>"]
    }
  ]
}

Rules:
- Generate exactly the requested number of questions.
- Use realistic {$examType}-style formatting and difficulty distribution.
- For Math sections, use LaTeX for all mathematical notation.
- For English/Reading sections, include relevant passages.
- Multiple-choice questions have 4 options (A-D) for ACT, or 4 options for SAT.
- grid_in questions (SAT Math) have options as an empty array.
- Each question must have a clear, unambiguous correct answer.{$passageInstructions}
SYSTEM;

    $user = "Generate a {$examType} {$sectionName} section with {$numQuestions} questions. "
          . "Time limit: {$timeMinutes} minutes."
          . ($subjectArea ? " Subject focus: {$subjectArea}." : '');

    return ['system' => $system, 'user' => $user];
}
