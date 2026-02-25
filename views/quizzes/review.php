<?php
/**
 * Quiz Review Page
 */
$currentPage = 'quizzes';
$pageTitle   = 'Quiz Review';
$currentUser = $currentUser ?? getCurrentUser();
$quizId      = $pageParams['id'] ?? '';

ob_start();
?>
<div class="mx-auto max-w-4xl space-y-6" data-quiz-id="<?= htmlspecialchars($quizId) ?>">
    <!-- Score Summary -->
    <div id="score-summary" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200 text-center">
        <div id="score-loading" class="py-8 text-neutral-500">Loading results…</div>
        <div id="score-content" class="hidden">
            <div class="text-5xl font-extrabold text-primary-600" id="score-value">—</div>
            <p class="mt-2 text-neutral-600" id="score-detail"></p>
        </div>
    </div>

    <!-- Generate Explanations -->
    <div class="flex justify-center">
        <button id="explain-btn" type="button" class="inline-flex items-center gap-2 rounded-lg border border-primary-300 bg-primary-50 px-5 py-2.5 text-sm font-semibold text-primary-700 hover:bg-primary-100 transition">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"/></svg>
            Generate AI Explanations
        </button>
    </div>

    <!-- Questions Review -->
    <div id="questions-review" class="space-y-4"></div>
</div>

<script>
(async () => {
    const quizId = document.querySelector('[data-quiz-id]').dataset.quizId;
    let quiz = null;

    try {
        const res = await fetch(`/api/quizzes/${encodeURIComponent(quizId)}`, { headers: { 'X-CSRF-Token': window.__CSRF__ } });
        quiz = await res.json();
    } catch { return; }

    if (quiz.error) { document.getElementById('score-loading').textContent = quiz.error; return; }

    // Score
    document.getElementById('score-loading').classList.add('hidden');
    document.getElementById('score-content').classList.remove('hidden');
    const score = quiz.score ?? quiz.result?.score;
    document.getElementById('score-value').textContent = score != null ? `${score}%` : '—';
    const total = (quiz.questions || []).length;
    const correct = quiz.result?.correct ?? Math.round((score || 0) * total / 100);
    document.getElementById('score-detail').textContent = `${correct} of ${total} questions correct`;

    // Questions
    const container = document.getElementById('questions-review');
    (quiz.questions || []).forEach((q, i) => {
        const userAnswer = quiz.answers?.[i] ?? quiz.result?.answers?.[i];
        const correctAnswer = q.correctAnswer ?? q.answer;
        const isCorrect = JSON.stringify(userAnswer) === JSON.stringify(correctAnswer);

        const div = document.createElement('div');
        div.className = `rounded-2xl bg-white p-6 shadow-sm ring-1 ${isCorrect ? 'ring-green-200' : 'ring-red-200'}`;
        div.innerHTML = `
            <div class="flex items-start justify-between mb-3">
                <span class="text-sm font-medium text-neutral-500">Question ${i + 1}</span>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${isCorrect ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">${isCorrect ? '✓ Correct' : '✗ Incorrect'}</span>
            </div>
            <div class="question-stem text-neutral-900 mb-4"></div>
            ${!isCorrect ? `<div class="rounded-lg bg-red-50 border border-red-200 p-3 mb-2"><p class="text-sm text-red-700"><strong>Your answer:</strong> ${_esc(String(userAnswer ?? 'No answer'))}</p></div>` : ''}
            <div class="rounded-lg bg-green-50 border border-green-200 p-3 mb-2"><p class="text-sm text-green-700"><strong>Correct answer:</strong> ${_esc(String(correctAnswer ?? ''))}</p></div>
            <div class="explanation hidden mt-4 rounded-lg bg-primary-50 border border-primary-200 p-4 text-sm text-neutral-700"></div>
        `;
        container.appendChild(div);
        window.renderContent?.(q.question || q.stem || '', div.querySelector('.question-stem'));
    });

    // Explanations
    document.getElementById('explain-btn').addEventListener('click', async function() {
        const modal = document.getElementById('ai-modal');
        document.getElementById('ai-modal-preview').textContent = JSON.stringify({ quizId, action: 'explain' }, null, 2);
        modal.classList.remove('hidden');

        const sendBtn = document.getElementById('ai-modal-send');
        const handler = async () => {
            sendBtn.removeEventListener('click', handler);
            modal.classList.add('hidden');
            this.disabled = true;
            this.innerHTML = '<svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Generating…';

            try {
                const res = await fetch('/api/ai/explain', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.__CSRF__ },
                    body: JSON.stringify({ quizId })
                });
                const data = await res.json();
                const explanations = data.explanations || [];
                container.querySelectorAll('.explanation').forEach((el, i) => {
                    if (explanations[i]) { el.classList.remove('hidden'); window.renderContent?.(explanations[i], el); }
                });
                this.textContent = '✓ Explanations Generated';
            } catch {
                this.textContent = 'Failed — Retry';
                this.disabled = false;
            }
        };
        sendBtn.addEventListener('click', handler);
    });
})();
function _esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
