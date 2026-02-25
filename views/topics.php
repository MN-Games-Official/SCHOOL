<?php
/**
 * Topics Page
 */
$currentPage = 'topics';
$pageTitle   = 'Topics';
$currentUser = $currentUser ?? getCurrentUser();

ob_start();
?>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-neutral-900">Topics</h2>
            <p class="mt-1 text-neutral-600">Browse and explore available learning topics.</p>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input type="search" id="topic-search" placeholder="Search topics…"
                   class="block w-full rounded-lg border border-neutral-300 bg-white pl-10 pr-4 py-2.5 text-sm placeholder-neutral-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition"
                   aria-label="Search topics">
        </div>
    </div>

    <!-- Subject Filter Tabs -->
    <div class="flex flex-wrap gap-2" id="subject-filters" role="tablist" aria-label="Filter by subject">
        <button class="subject-tab active rounded-full px-4 py-1.5 text-sm font-medium bg-primary-100 text-primary-700 border border-primary-200 transition" data-subject="all" role="tab" aria-selected="true">All</button>
    </div>

    <!-- Topics Grid -->
    <div id="topics-grid" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <!-- Loading state -->
        <div id="topics-loading" class="col-span-full flex justify-center py-12">
            <div class="flex items-center gap-3 text-neutral-500">
                <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Loading topics…
            </div>
        </div>
    </div>

    <!-- Empty state -->
    <div id="topics-empty" class="hidden text-center py-12">
        <svg class="mx-auto h-12 w-12 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
        <p class="mt-3 text-sm text-neutral-500">No topics found matching your search.</p>
    </div>
</div>

<script>
(async () => {
    const grid = document.getElementById('topics-grid');
    const loading = document.getElementById('topics-loading');
    const empty = document.getElementById('topics-empty');
    const search = document.getElementById('topic-search');
    const filtersEl = document.getElementById('subject-filters');
    let topics = [];
    let activeSubject = 'all';

    try {
        const res = await fetch('/api/topics');
        topics = await res.json();
        if (!Array.isArray(topics)) topics = topics.topics || [];
    } catch { topics = []; }

    // Build subject tabs
    const subjects = [...new Set(topics.map(t => t.subject).filter(Boolean))];
    subjects.forEach(s => {
        const btn = document.createElement('button');
        btn.className = 'subject-tab rounded-full px-4 py-1.5 text-sm font-medium bg-white text-neutral-600 border border-neutral-200 hover:border-primary-300 transition';
        btn.dataset.subject = s;
        btn.setAttribute('role', 'tab');
        btn.setAttribute('aria-selected', 'false');
        btn.textContent = s;
        filtersEl.appendChild(btn);
    });

    filtersEl.addEventListener('click', (e) => {
        const btn = e.target.closest('.subject-tab');
        if (!btn) return;
        filtersEl.querySelectorAll('.subject-tab').forEach(b => {
            b.classList.remove('active','bg-primary-100','text-primary-700','border-primary-200');
            b.classList.add('bg-white','text-neutral-600','border-neutral-200');
            b.setAttribute('aria-selected', 'false');
        });
        btn.classList.add('active','bg-primary-100','text-primary-700','border-primary-200');
        btn.classList.remove('bg-white','text-neutral-600','border-neutral-200');
        btn.setAttribute('aria-selected', 'true');
        activeSubject = btn.dataset.subject;
        renderTopics();
    });

    search.addEventListener('input', () => renderTopics());

    function renderTopics() {
        const q = search.value.toLowerCase();
        let filtered = topics.filter(t => {
            const matchSubject = activeSubject === 'all' || t.subject === activeSubject;
            const matchSearch = !q || t.name?.toLowerCase().includes(q) || t.subject?.toLowerCase().includes(q) || (t.tags || []).some(tag => tag.toLowerCase().includes(q));
            return matchSubject && matchSearch;
        });

        loading.classList.add('hidden');
        if (!filtered.length) { grid.innerHTML = ''; empty.classList.remove('hidden'); return; }
        empty.classList.add('hidden');

        grid.innerHTML = filtered.map(t => `
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-neutral-200 hover:ring-primary-300 hover:shadow-md transition cursor-pointer" tabindex="0" role="button" aria-label="View topic: ${_esc(t.name)}">
                <h3 class="font-semibold text-neutral-900">${_esc(t.name)}</h3>
                <p class="mt-1 text-xs text-primary-600 font-medium">${_esc(t.subject || '')}</p>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    ${(t.tags || []).map(tag => `<span class="inline-flex rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs text-neutral-600">${_esc(tag)}</span>`).join('')}
                </div>
                ${(t.testMappings && t.testMappings.length) ? `<div class="mt-3 flex flex-wrap gap-1.5">${t.testMappings.map(m => `<span class="inline-flex rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700">${_esc(typeof m === 'string' ? m : m.test || '')}</span>`).join('')}</div>` : ''}
            </div>
        `).join('');

        grid.querySelectorAll('[role="button"]').forEach((card, i) => {
            card.addEventListener('click', () => window.location.href = '/lessons/generate?topic=' + encodeURIComponent(filtered[i].id || filtered[i].name));
            card.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }});
        });
    }

    renderTopics();
})();

function _esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layout.php';
?>
