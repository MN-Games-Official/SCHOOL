<?php
/**
 * Prompt Template – Writing Scan
 *
 * Instructs the AI to scan a document and return diagnostics ONLY —
 * no rewrites, no corrected text. Identifies issues such as unclear
 * sentences, missing thesis, organisation problems, evidence gaps,
 * and tone inconsistencies.
 *
 * @package School
 */

/**
 * Generate prompt messages for writing scan.
 *
 * @param  array $params  Expected keys: content (string), doc_type (optional).
 * @return array          ['system' => '…', 'user' => '…']
 */
function getPrompt_writing_scan(array $params): array
{
    $content = $params['content']  ?? '';
    $docType = $params['doc_type'] ?? 'essay';

    $system = <<<'SYSTEM'
You are a writing coach performing a diagnostic scan. You MUST respond ONLY with a valid JSON object — no markdown fences, no extra text. Do NOT rewrite any part of the document.

The JSON must conform to this schema:

{
  "issues": [
    {
      "type": "unclear_sentence | missing_thesis | organization | evidence_gap | tone | grammar | style",
      "severity": "info | warning | error",
      "location": "<paragraph number or sentence excerpt>",
      "message": "<clear description of the issue>",
      "suggestion": "<actionable advice for the student to fix it themselves>"
    }
  ],
  "summary": "<2-3 sentence overall assessment>",
  "strengths": ["<strength1>", "<strength2>"]
}

Rules:
- Identify concrete, specific issues — not vague generalities.
- Do NOT provide corrected text or rewrites.
- Suggestions should guide the student to improve on their own.
- Cover these categories: unclear sentences, missing or weak thesis, organizational flow, evidence/support gaps, tone consistency, grammar, and style.
- Severity levels: "error" = significantly harms the writing, "warning" = should be addressed, "info" = minor improvement opportunity.
SYSTEM;

    $user = "Scan this {$docType} for issues:\n\n{$content}";

    return ['system' => $system, 'user' => $user];
}
