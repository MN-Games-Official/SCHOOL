<?php
/**
 * Quiz Taking Page
 */
$currentPage = 'quizzes';
$pageTitle   = 'Take Quiz';
$currentUser = $currentUser ?? getCurrentUser();
$quizId      = $pageParams['id'] ?? '';

ob_start();
?>
<div class="mx-auto max-w-5xl" id="quiz-app" data-quiz-id="<?= htmlspecialchars($quizId) ?>">
    <div class="flex gap-6">
        <!-- Question Area -->
        <div class="flex-1 space-y-6">
            <!-- Quiz header -->
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-neutral-900" id="quiz-title">Loading quiz…</h2>
                <div id="quiz-timer" class="hidden items-center gap-2 rounded-lg bg-neutral-100 px-3 py-1.5 text-sm font-mono text-neutral-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <span id="timer-display">00:00</span>
                </div>
            </div>

            <!-- Question Card -->
            <div id="question-card" class="rounded-2xl bg-white p-6 sm:p-8 shadow-sm ring-1 ring-neutral-200 min-h-[300px]">
                <div id="question-loading" class="flex justify-center py-12">
                    <div class="flex items-center gap-3 text-neutral-500">
                        <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Loading…
                    </div>
                </div>

                <div id="question-content" class="hidden space-y-6">
                    <div class="flex items-center gap-2 text-sm text-neutral-500">
                        <span id="q-number"></span>
                        <span id="q-type-badge" class="rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs"></span>
                    </div>
                    <div id="q-stem" class="text-neutral-900 leading-relaxed"></div>
                    <div id="q-choices" class="space-y-2"></div>
                    <div id="q-input-area" class="hidden">
                        <input type="text" id="q-text-input" class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition" placeholder="Type your answer…" aria-label="Your answer">
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex items-center justify-between">
                <button id="prev-btn" type="button" class="rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 transition disabled:opacity-50" disabled>← Previous</button>
                <button id="next-btn" type="button" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition">Next →</button>
                <button id="submit-quiz-btn" type="button" class="hidden rounded-lg bg-green-600 px-6 py-2 text-sm font-semibold text-white hover:bg-green-700 transition">Submit Quiz</button>
            </div>
        </div>

        <!-- Question Navigation Sidebar -->
        <div class="hidden lg:block w-48 shrink-0">
            <div class="sticky top-20 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-200">
                <h3 class="text-sm font-semibold text-neutral-700 mb-3">Questions</h3>
                <div id="q-nav" class="grid grid-cols-5 gap-2" role="navigation" aria-label="Question navigation"></div>
            </div>
        </div>
    </div>
</div>

<script>
(async () => {
    const quizId = document.getElementById('quiz-app').dataset.quizId;
    let quiz = null, current = 0, answers = {};

    try {
        const res = await fetch(`/api/quizzes/${encodeURIComponent(quizId)}`, { headers: { 'X-CSRF-Token': window.__CSRF__ } });
        quiz = await res.json();
    } catch { document.getElementById('question-loading').innerHTML = '<p class="text-red-600">Failed to load quiz.</p>'; return; }

    if (quiz.error) { document.getElementById('question-loading').innerHTML = `<p class="text-red-600">${_esc(quiz.error)}</p>`; return; }

    const questions = quiz.questions || [];
    document.getElementById('quiz-title').textContent = quiz.title || 'Quiz';
    document.getElementById('question-loading').classList.add('hidden');
    document.getElementById('question-content').classList.remove('hidden');

    // Build nav
    const nav = document.getElementById('q-nav');
    questions.forEach((_, i) => {
        const btn = document.createElement('button');
        btn.className = 'h-8 w-8 rounded-lg border text-xs font-medium transition';
        btn.textContent = i + 1;
        btn.setAttribute('aria-label', `Question ${i + 1}`);
        btn.addEventListener('click', () => { current = i; renderQuestion(); });
        nav.appendChild(btn);
    });

    function renderQuestion() {
        const q = questions[current];
        if (!q) return;
        document.getElementById('q-number').textContent = `Question ${current + 1} of ${questions.length}`;
        document.getElementById('q-type-badge').textContent = (q.type || 'multiple_choice').replace(/_/g, ' ');

        const stem = document.getElementById('q-stem');
        window.renderContent?.(q.question || q.stem || '', stem) || (stem.textContent = q.question || q.stem || '');

        const choicesEl = document.getElementById('q-choices');
        const inputArea = document.getElementById('q-input-area');
        choicesEl.innerHTML = '';
        inputArea.classList.add('hidden');

        if (q.type === 'short_answer' || q.type === 'numeric_entry') {
            inputArea.classList.remove('hidden');
            const input = document.getElementById('q-text-input');
            input.value = answers[current] || '';
            input.type = q.type === 'numeric_entry' ? 'number' : 'text';
            input.oninput = () => { answers[current] = input.value; updateNav(); };
        } else {
            const isMulti = q.type === 'multi_select';
            (q.choices || q.options || []).forEach((opt, i) => {
                const label = document.createElement('label');
                const selected = isMulti ? (answers[current] || []).includes(i) : answers[current] === i;
                label.className = `flex items-center gap-3 rounded-lg border p-4 cursor-pointer transition ${selected ? 'border-primary-500 bg-primary-50' : 'border-neutral-200 hover:bg-neutral-50'}`;
                label.innerHTML = `
                    <input type="${isMulti ? 'checkbox' : 'radio'}" name="q${current}" value="${i}" ${selected ? 'checked' : ''} class="sr-only">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 text-xs font-semibold ${selected ? 'border-primary-600 bg-primary-600 text-white' : 'border-neutral-300 text-neutral-500'}">${String.fromCharCode(65 + i)}</span>
                    <span class="text-sm text-neutral-700">${_esc(typeof opt === 'string' ? opt : opt.text || '')}</span>
                `;
                label.addEventListener('click', () => {
                    if (isMulti) {
                        if (!answers[current]) answers[current] = [];
                        const idx = answers[current].indexOf(i);
                        idx >= 0 ? answers[current].splice(idx, 1) : answers[current].push(i);
                    } else { answers[current] = i; }
                    renderQuestion();
                });
                choicesEl.appendChild(label);
            });
        }

        // Nav buttons
        document.getElementById('prev-btn').disabled = current === 0;
        const isLast = current === questions.length - 1;
        document.getElementById('next-btn').classList.toggle('hidden', isLast);
        document.getElementById('submit-quiz-btn').classList.toggle('hidden', !isLast);
        updateNav();
    }

    function updateNav() {
        nav.querySelectorAll('button').forEach((btn, i) => {
            const answered = answers[i] !== undefined && answers[i] !== '' && (!Array.isArray(answers[i]) || answers[i].length);
            const isCurrent = i === current;
            btn.className = `h-8 w-8 rounded-lg border text-xs font-medium transition ${isCurrent ? 'border-primary-500 bg-primary-600 text-white' : answered ? 'border-primary-300 bg-primary-100 text-primary-700' : 'border-neutral-200 text-neutral-500 hover:bg-neutral-50'}`;
        });
    }

    document.getElementById('prev-btn').addEventListener('click', () => { if (current > 0) { current--; renderQuestion(); } });
    document.getElementById('next-btn').addEventListener('click', () => { if (current < questions.length - 1) { current++; renderQuestion(); } });
    document.getElementById('submit-quiz-btn').addEventListener('click', async () => {
        if (!confirm('Submit your quiz? You cannot change answers after submission.')) return;
        try {
            const res = await fetch(`/api/quizzes/${encodeURIComponent(quizId)}/grade`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.__CSRF__ },
                body: JSON.stringify({ answers })
            });
            const data = await res.json();
            if (data.error) { window.showToast?.(data.error, 'error'); return; }
            window.location.href = `/quizzes/review/${encodeURIComponent(quizId)}`;
        } catch { window.showToast?.('Submission failed.', 'error'); }
    });

    renderQuestion();
})();
function _esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
