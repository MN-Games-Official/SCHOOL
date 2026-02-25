<?php
/**
 * Lessons Library
 */
$currentPage = 'lessons';
$pageTitle   = 'Lessons';
$currentUser = $currentUser ?? getCurrentUser();

ob_start();
?>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-neutral-900">Lessons</h2>
            <p class="mt-1 text-neutral-600">Your generated lessons library.</p>
        </div>
        <a href="/lessons/generate" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-primary-700 transition">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Generate New Lesson
        </a>
    </div>

    <!-- Lessons list -->
    <div id="lessons-list" class="space-y-3">
        <!-- Loading -->
        <div id="lessons-loading" class="flex justify-center py-12">
            <div class="flex items-center gap-3 text-neutral-500">
                <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Loading lessons…
            </div>
        </div>
    </div>

    <!-- Empty state -->
    <div id="lessons-empty" class="hidden text-center py-12">
        <svg class="mx-auto h-12 w-12 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347"/></svg>
        <h3 class="mt-3 text-sm font-semibold text-neutral-900">No lessons yet</h3>
        <p class="mt-1 text-sm text-neutral-500">Generate your first AI-powered lesson to get started.</p>
        <a href="/lessons/generate" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Generate Lesson
        </a>
    </div>
</div>

<script>
(async () => {
    const list = document.getElementById('lessons-list');
    const loading = document.getElementById('lessons-loading');
    const empty = document.getElementById('lessons-empty');

    try {
        const res = await fetch('/api/lessons/list', { headers: { 'X-CSRF-Token': window.__CSRF__ } });
        const data = await res.json();
        const lessons = Array.isArray(data) ? data : data.lessons || [];
        loading.classList.add('hidden');

        if (!lessons.length) { empty.classList.remove('hidden'); return; }

        list.innerHTML = lessons.map(l => `
            <a href="/lessons/generate?view=${encodeURIComponent(l.id)}" class="block rounded-2xl bg-white p-5 shadow-sm ring-1 ring-neutral-200 hover:ring-primary-300 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-neutral-900">${_esc(l.title || 'Untitled Lesson')}</h3>
                    <span class="text-xs text-neutral-500">${l.createdAt ? new Date(l.createdAt).toLocaleDateString() : ''}</span>
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    ${l.topic ? `<span class="inline-flex rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700">${_esc(typeof l.topic === 'string' ? l.topic : l.topic.name || '')}</span>` : ''}
                    ${l.difficulty ? `<span class="inline-flex rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs text-neutral-600">Level ${_esc(String(l.difficulty))}</span>` : ''}
                </div>
            </a>
        `).join('');
    } catch {
        loading.innerHTML = '<p class="text-sm text-red-600">Failed to load lessons.</p>';
    }
})();
function _esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
