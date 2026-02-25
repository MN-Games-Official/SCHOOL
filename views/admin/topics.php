<?php
/**
 * Admin Topics View (read-only)
 */
$currentPage = 'admin-topics';
$pageTitle   = 'Admin Topics';
$currentUser = $currentUser ?? getCurrentUser();

ob_start();
?>
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-neutral-900">Admin Topics</h2>
        <p class="mt-1 text-neutral-600">Read-only view of the topics database.</p>
    </div>

    <!-- Search -->
    <div class="relative max-w-md">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
        <input type="search" id="admin-search" placeholder="Filter topics…"
               class="block w-full rounded-lg border border-neutral-300 bg-white pl-10 pr-4 py-2.5 text-sm placeholder-neutral-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition"
               aria-label="Filter topics">
    </div>

    <!-- Table -->
    <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-neutral-200">
        <table class="min-w-full divide-y divide-neutral-200" role="table">
            <thead class="bg-neutral-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">Name</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">Subject</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">Tags</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">Test Mappings</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">ID</th>
                </tr>
            </thead>
            <tbody id="admin-tbody" class="divide-y divide-neutral-100">
                <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-neutral-500">Loading topics…</td></tr>
            </tbody>
        </table>
    </div>

    <p id="admin-count" class="text-xs text-neutral-400"></p>
</div>

<script>
(async () => {
    const tbody = document.getElementById('admin-tbody');
    const search = document.getElementById('admin-search');
    const countEl = document.getElementById('admin-count');
    let topics = [];

    try {
        const res = await fetch('/api/topics');
        const data = await res.json();
        topics = Array.isArray(data) ? data : data.topics || [];
    } catch { tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-red-600">Failed to load topics.</td></tr>'; return; }

    function render() {
        const q = search.value.toLowerCase();
        const filtered = topics.filter(t => !q || (t.name || '').toLowerCase().includes(q) || (t.subject || '').toLowerCase().includes(q) || (t.id || '').toLowerCase().includes(q));
        countEl.textContent = `Showing ${filtered.length} of ${topics.length} topics`;
        if (!filtered.length) { tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-neutral-500">No matching topics.</td></tr>'; return; }
        tbody.innerHTML = filtered.map(t => `
            <tr class="hover:bg-neutral-50 transition">
                <td class="px-4 py-3 text-sm font-medium text-neutral-900">${_esc(t.name)}</td>
                <td class="px-4 py-3 text-sm text-neutral-600">${_esc(t.subject || '')}</td>
                <td class="px-4 py-3"><div class="flex flex-wrap gap-1">${(t.tags || []).map(tag => `<span class="inline-flex rounded-full bg-neutral-100 px-2 py-0.5 text-xs text-neutral-600">${_esc(tag)}</span>`).join('')}</div></td>
                <td class="px-4 py-3"><div class="flex flex-wrap gap-1">${(t.testMappings || []).map(m => `<span class="inline-flex rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700">${_esc(typeof m === 'string' ? m : m.test || JSON.stringify(m))}</span>`).join('')}</div></td>
                <td class="px-4 py-3 text-xs font-mono text-neutral-400">${_esc(t.id || '')}</td>
            </tr>
        `).join('');
    }

    search.addEventListener('input', render);
    render();
})();
function _esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
