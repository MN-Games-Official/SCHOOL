<?php
/**
 * Quiz Library
 */
$currentPage = 'quizzes';
$pageTitle   = 'Quizzes';
$currentUser = $currentUser ?? getCurrentUser();

ob_start();
?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-neutral-900">Quizzes</h2>
            <p class="mt-1 text-neutral-600">Your quiz history and scores.</p>
        </div>
        <a href="/quizzes/generate" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-primary-700 transition">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Generate New Quiz
        </a>
    </div>

    <div id="quizzes-list" class="space-y-3">
        <div id="quizzes-loading" class="flex justify-center py-12">
            <div class="flex items-center gap-3 text-neutral-500">
                <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Loading quizzes…
            </div>
        </div>
    </div>

    <div id="quizzes-empty" class="hidden text-center py-12">
        <svg class="mx-auto h-12 w-12 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08"/></svg>
        <h3 class="mt-3 text-sm font-semibold text-neutral-900">No quizzes yet</h3>
        <p class="mt-1 text-sm text-neutral-500">Generate a quiz to test your knowledge.</p>
        <a href="/quizzes/generate" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition">Generate Quiz</a>
    </div>
</div>

<script>
(async () => {
    const list = document.getElementById('quizzes-list');
    const loading = document.getElementById('quizzes-loading');
    const empty = document.getElementById('quizzes-empty');
    try {
        const res = await fetch('/api/quizzes/list', { headers: { 'X-CSRF-Token': window.__CSRF__ } });
        const data = await res.json();
        const quizzes = Array.isArray(data) ? data : data.quizzes || [];
        loading.classList.add('hidden');
        if (!quizzes.length) { empty.classList.remove('hidden'); return; }
        list.innerHTML = quizzes.map(q => {
            const score = q.score != null ? `<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${q.score >= 80 ? 'bg-green-100 text-green-700' : q.score >= 60 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'}">${q.score}%</span>` : '<span class="text-xs text-neutral-400">Not graded</span>';
            return `<a href="/quizzes/review/${encodeURIComponent(q.id)}" class="block rounded-2xl bg-white p-5 shadow-sm ring-1 ring-neutral-200 hover:ring-primary-300 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-neutral-900">${_esc(q.title || 'Quiz')}</h3>
                    <span class="text-xs text-neutral-500">${q.createdAt ? new Date(q.createdAt).toLocaleDateString() : ''}</span>
                </div>
                <div class="mt-2 flex items-center gap-3">
                    ${score}
                    ${q.questionCount ? `<span class="text-xs text-neutral-500">${q.questionCount} questions</span>` : ''}
                </div>
            </a>`;
        }).join('');
    } catch {
        loading.innerHTML = '<p class="text-sm text-red-600">Failed to load quizzes.</p>';
    }
})();
function _esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
