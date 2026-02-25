<?php
/**
 * Exams Hub
 */
$currentPage = 'exams';
$pageTitle   = 'Exams';
$currentUser = $currentUser ?? getCurrentUser();

ob_start();
?>
<div class="space-y-8">
    <div>
        <h2 class="text-2xl font-bold text-neutral-900">Exams</h2>
        <p class="mt-1 text-neutral-600">Practice with full-length standardized exams or build topic-based exams.</p>
    </div>

    <!-- Exam Type Cards -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="/exams/builder?type=sat" class="group rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200 hover:ring-primary-300 hover:shadow-md transition text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-100 text-primary-600 group-hover:bg-primary-600 group-hover:text-white transition">
                <span class="text-2xl font-extrabold">SAT</span>
            </div>
            <h3 class="font-semibold text-neutral-900">SAT</h3>
            <p class="mt-1 text-xs text-neutral-500">Reading, Writing & Math</p>
        </a>
        <a href="/exams/builder?type=act" class="group rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200 hover:ring-emerald-300 hover:shadow-md transition text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition">
                <span class="text-2xl font-extrabold">ACT</span>
            </div>
            <h3 class="font-semibold text-neutral-900">ACT</h3>
            <p class="mt-1 text-xs text-neutral-500">English, Math, Reading, Science</p>
        </a>
        <a href="/exams/builder?type=preact" class="group rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200 hover:ring-amber-300 hover:shadow-md transition text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition">
                <span class="text-lg font-extrabold">Pre<br>ACT</span>
            </div>
            <h3 class="font-semibold text-neutral-900">PreACT</h3>
            <p class="mt-1 text-xs text-neutral-500">Practice for the ACT</p>
        </a>
        <a href="/exams/builder?type=mca" class="group rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200 hover:ring-rose-300 hover:shadow-md transition text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 group-hover:bg-rose-600 group-hover:text-white transition">
                <span class="text-2xl font-extrabold">MCA</span>
            </div>
            <h3 class="font-semibold text-neutral-900">MCA</h3>
            <p class="mt-1 text-xs text-neutral-500">MN Comprehensive Assessments</p>
        </a>
    </div>

    <!-- Topic-based builder -->
    <div class="rounded-2xl bg-gradient-to-r from-primary-50 to-primary-100 p-6 ring-1 ring-primary-200">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-primary-900">Topic-Based Exam Builder</h3>
                <p class="mt-1 text-sm text-primary-700">Create a custom exam from any combination of topics.</p>
            </div>
            <a href="/exams/builder?type=topic" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-primary-700 transition">Build Exam</a>
        </div>
    </div>

    <!-- Attempt History -->
    <div>
        <h3 class="text-lg font-semibold text-neutral-900 mb-4">Attempt History</h3>
        <div id="exams-list" class="space-y-3">
            <div id="exams-loading" class="flex justify-center py-8">
                <div class="flex items-center gap-3 text-neutral-500">
                    <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Loading…
                </div>
            </div>
        </div>
        <div id="exams-empty" class="hidden text-center py-8">
            <p class="text-sm text-neutral-500">No exams taken yet. Choose an exam type above to get started.</p>
        </div>
    </div>
</div>

<script>
(async () => {
    const list = document.getElementById('exams-list');
    const loading = document.getElementById('exams-loading');
    const empty = document.getElementById('exams-empty');
    try {
        const res = await fetch('/api/exams/list', { headers: { 'X-CSRF-Token': window.__CSRF__ } });
        const data = await res.json();
        const exams = Array.isArray(data) ? data : data.exams || [];
        loading.classList.add('hidden');
        if (!exams.length) { empty.classList.remove('hidden'); return; }
        list.innerHTML = exams.map(e => `
            <a href="/exams/review/${encodeURIComponent(e.id)}" class="block rounded-2xl bg-white p-5 shadow-sm ring-1 ring-neutral-200 hover:ring-primary-300 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <h4 class="font-semibold text-neutral-900">${_esc(e.type?.toUpperCase() || 'Exam')} ${e.title ? '— ' + _esc(e.title) : ''}</h4>
                    <span class="text-xs text-neutral-500">${e.createdAt ? new Date(e.createdAt).toLocaleDateString() : ''}</span>
                </div>
                <div class="mt-2 flex items-center gap-3">
                    ${e.score != null ? `<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${e.score >= 70 ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'}">${e.score}%</span>` : ''}
                    ${e.scaledScore ? `<span class="text-xs text-neutral-500">Scaled: ${e.scaledScore}</span>` : ''}
                </div>
            </a>
        `).join('');
    } catch { loading.innerHTML = '<p class="text-sm text-red-600">Failed to load exams.</p>'; }
})();
function _esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
