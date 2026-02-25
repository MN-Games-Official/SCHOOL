<?php
/**
 * Exam Review
 */
$currentPage = 'exams';
$pageTitle   = 'Exam Review';
$currentUser = $currentUser ?? getCurrentUser();
$examId      = $pageParams['id'] ?? '';

ob_start();
?>
<div class="mx-auto max-w-5xl space-y-6" data-exam-id="<?= htmlspecialchars($examId) ?>">
    <!-- Score Overview -->
    <div id="exam-score-card" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
        <div id="exam-score-loading" class="text-center py-8 text-neutral-500">Loading results…</div>
        <div id="exam-score-content" class="hidden">
            <div class="grid gap-6 sm:grid-cols-3 text-center">
                <div>
                    <p class="text-sm text-neutral-500">Overall Score</p>
                    <p class="text-4xl font-extrabold text-primary-600" id="exam-overall-score">—</p>
                </div>
                <div>
                    <p class="text-sm text-neutral-500">Scaled Score</p>
                    <p class="text-4xl font-extrabold text-neutral-900" id="exam-scaled-score">—</p>
                </div>
                <div>
                    <p class="text-sm text-neutral-500">Total Questions</p>
                    <p class="text-4xl font-extrabold text-neutral-900" id="exam-total-q">—</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Tabs -->
    <div id="section-tabs" class="flex flex-wrap gap-2 border-b border-neutral-200 pb-2" role="tablist" aria-label="Exam sections"></div>

    <!-- Section Score Breakdown -->
    <div id="section-breakdown" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200 hidden">
        <h3 class="text-lg font-semibold text-neutral-900 mb-4" id="section-title">Section</h3>
        <div class="grid gap-4 sm:grid-cols-2 mb-6">
            <div class="rounded-lg bg-neutral-50 border border-neutral-200 p-4">
                <p class="text-sm text-neutral-500">Section Score</p>
                <p class="text-2xl font-bold text-neutral-900" id="section-score">—</p>
            </div>
            <div class="rounded-lg bg-neutral-50 border border-neutral-200 p-4">
                <p class="text-sm text-neutral-500">Correct / Total</p>
                <p class="text-2xl font-bold text-neutral-900" id="section-correct">—</p>
            </div>
        </div>

        <!-- Domain/Topic Breakdown -->
        <div id="domain-breakdown" class="space-y-3 mb-6">
            <h4 class="text-sm font-semibold text-neutral-700">Domain Breakdown</h4>
        </div>

        <!-- Question Review -->
        <div id="section-questions" class="space-y-4"></div>
    </div>
</div>

<script>
(async () => {
    const examId = document.querySelector('[data-exam-id]').dataset.examId;
    let exam = null;

    try {
        const res = await fetch(`/api/exams/${encodeURIComponent(examId)}`, { headers: { 'X-CSRF-Token': window.__CSRF__ } });
        exam = await res.json();
    } catch { return; }

    if (exam.error) { document.getElementById('exam-score-loading').textContent = exam.error; return; }

    document.getElementById('exam-score-loading').classList.add('hidden');
    document.getElementById('exam-score-content').classList.remove('hidden');
    document.getElementById('exam-overall-score').textContent = (exam.score ?? exam.result?.score ?? '—') + '%';
    document.getElementById('exam-scaled-score').textContent = exam.scaledScore ?? exam.result?.scaledScore ?? '—';

    const sections = exam.sections || [];
    const totalQ = sections.reduce((s, sec) => s + (sec.questions || []).length, 0);
    document.getElementById('exam-total-q').textContent = totalQ;

    // Section tabs
    const tabsEl = document.getElementById('section-tabs');
    sections.forEach((sec, i) => {
        const btn = document.createElement('button');
        btn.className = 'px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 transition';
        btn.textContent = sec.name || `Section ${i + 1}`;
        btn.setAttribute('role', 'tab');
        btn.addEventListener('click', () => showSection(i));
        tabsEl.appendChild(btn);
    });

    function showSection(idx) {
        const sec = sections[idx];
        tabsEl.querySelectorAll('button').forEach((b, i) => {
            b.className = `px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 transition ${i === idx ? 'border-primary-600 text-primary-600' : 'border-transparent text-neutral-500 hover:text-neutral-700'}`;
            b.setAttribute('aria-selected', i === idx ? 'true' : 'false');
        });

        const breakdown = document.getElementById('section-breakdown');
        breakdown.classList.remove('hidden');
        document.getElementById('section-title').textContent = sec.name || `Section ${idx + 1}`;
        document.getElementById('section-score').textContent = (sec.score ?? '—') + '%';
        const questions = sec.questions || [];
        const correct = questions.filter(q => q.isCorrect).length;
        document.getElementById('section-correct').textContent = `${correct} / ${questions.length}`;

        // Domain breakdown
        const domains = {};
        questions.forEach(q => { const d = q.domain || q.topic || 'General'; if (!domains[d]) domains[d] = { total: 0, correct: 0 }; domains[d].total++; if (q.isCorrect) domains[d].correct++; });
        const domainEl = document.getElementById('domain-breakdown');
        domainEl.innerHTML = '<h4 class="text-sm font-semibold text-neutral-700">Domain Breakdown</h4>' +
            Object.entries(domains).map(([name, d]) => {
                const pct = Math.round(d.correct / d.total * 100);
                return `<div class="flex items-center gap-3"><span class="w-32 text-sm text-neutral-600 truncate">${_esc(name)}</span><div class="flex-1 h-2 rounded-full bg-neutral-200"><div class="h-2 rounded-full bg-primary-500" style="width:${pct}%"></div></div><span class="text-xs text-neutral-500 w-12 text-right">${pct}%</span></div>`;
            }).join('');

        // Questions
        const qEl = document.getElementById('section-questions');
        qEl.innerHTML = questions.map((q, i) => `
            <div class="rounded-xl p-4 ring-1 ${q.isCorrect ? 'ring-green-200 bg-green-50/50' : 'ring-red-200 bg-red-50/50'}">
                <div class="flex items-start justify-between mb-2">
                    <span class="text-sm font-medium text-neutral-500">Q${i + 1}</span>
                    <span class="text-xs font-semibold ${q.isCorrect ? 'text-green-700' : 'text-red-700'}">${q.isCorrect ? '✓ Correct' : '✗ Incorrect'}</span>
                </div>
                <div class="text-sm text-neutral-800 q-stem-review"></div>
                ${q.explanation ? `<div class="mt-3 rounded-lg bg-primary-50 border border-primary-200 p-3 text-sm text-neutral-700 q-explanation-review"></div>` : ''}
            </div>
        `).join('');

        qEl.querySelectorAll('.q-stem-review').forEach((el, i) => window.renderContent?.(questions[i].question || questions[i].stem || '', el));
        qEl.querySelectorAll('.q-explanation-review').forEach((el, i) => { if (questions[i].explanation) window.renderContent?.(questions[i].explanation, el); });
    }

    if (sections.length) showSection(0);
})();
function _esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
