<?php
/**
 * Writing Documents List
 */
$currentPage = 'writing';
$pageTitle   = 'Writing Studio';
$currentUser = $currentUser ?? getCurrentUser();

ob_start();
?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-neutral-900">Writing Studio</h2>
            <p class="mt-1 text-neutral-600">Your documents and writing projects.</p>
        </div>
        <button id="new-doc-btn" type="button" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-primary-700 transition">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            New Document
        </button>
    </div>

    <div id="docs-list" class="space-y-3">
        <div id="docs-loading" class="flex justify-center py-12">
            <div class="flex items-center gap-3 text-neutral-500">
                <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Loading documents…
            </div>
        </div>
    </div>

    <div id="docs-empty" class="hidden text-center py-12">
        <svg class="mx-auto h-12 w-12 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
        <h3 class="mt-3 text-sm font-semibold text-neutral-900">No documents yet</h3>
        <p class="mt-1 text-sm text-neutral-500">Create your first document to start writing.</p>
    </div>
</div>

<script>
(async () => {
    const list = document.getElementById('docs-list');
    const loading = document.getElementById('docs-loading');
    const empty = document.getElementById('docs-empty');

    try {
        const res = await fetch('/api/writing/list', { headers: { 'X-CSRF-Token': window.__CSRF__ } });
        const data = await res.json();
        const docs = Array.isArray(data) ? data : data.documents || [];
        loading.classList.add('hidden');
        if (!docs.length) { empty.classList.remove('hidden'); return; }
        list.innerHTML = docs.map(d => `
            <a href="/writing/edit/${encodeURIComponent(d.id)}" class="block rounded-2xl bg-white p-5 shadow-sm ring-1 ring-neutral-200 hover:ring-primary-300 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-neutral-900">${_esc(d.title || 'Untitled')}</h3>
                    <span class="text-xs text-neutral-500">${d.updatedAt ? new Date(d.updatedAt).toLocaleDateString() : ''}</span>
                </div>
                <div class="mt-2 flex items-center gap-3 text-xs text-neutral-500">
                    ${d.wordCount != null ? `<span>${d.wordCount} words</span>` : ''}
                </div>
            </a>
        `).join('');
    } catch { loading.innerHTML = '<p class="text-sm text-red-600">Failed to load documents.</p>'; }

    document.getElementById('new-doc-btn').addEventListener('click', async () => {
        try {
            const res = await fetch('/api/writing/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.__CSRF__ },
                body: JSON.stringify({ title: 'Untitled Document' })
            });
            const data = await res.json();
            if (data.id) window.location.href = '/writing/edit/' + data.id;
            else window.showToast?.(data.error || 'Failed to create document.', 'error');
        } catch { window.showToast?.('Network error.', 'error'); }
    });
})();
function _esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
