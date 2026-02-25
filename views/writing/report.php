<?php
/**
 * Writing Integrity Report
 */
$currentPage = 'writing';
$pageTitle   = 'Integrity Report';
$currentUser = $currentUser ?? getCurrentUser();
$docId       = $pageParams['id'] ?? '';

ob_start();
?>
<div class="mx-auto max-w-4xl space-y-6" data-doc-id="<?= htmlspecialchars($docId) ?>">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-neutral-900">Integrity Report</h2>
            <p class="mt-1 text-neutral-600">Document authenticity and writing process analysis.</p>
        </div>
        <button id="export-btn" type="button" class="inline-flex items-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 transition">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Export Report
        </button>
    </div>

    <!-- Document Metadata -->
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
        <h3 class="text-lg font-semibold text-neutral-900 mb-4">Document Metadata</h3>
        <dl id="doc-metadata" class="grid gap-4 sm:grid-cols-2">
            <div><dt class="text-sm text-neutral-500">Title</dt><dd class="text-sm font-medium text-neutral-900" id="meta-title">—</dd></div>
            <div><dt class="text-sm text-neutral-500">Author</dt><dd class="text-sm font-medium text-neutral-900" id="meta-author">—</dd></div>
            <div><dt class="text-sm text-neutral-500">Created</dt><dd class="text-sm font-medium text-neutral-900" id="meta-created">—</dd></div>
            <div><dt class="text-sm text-neutral-500">Last Modified</dt><dd class="text-sm font-medium text-neutral-900" id="meta-modified">—</dd></div>
            <div><dt class="text-sm text-neutral-500">Word Count</dt><dd class="text-sm font-medium text-neutral-900" id="meta-words">—</dd></div>
            <div><dt class="text-sm text-neutral-500">Verification Hash</dt><dd class="text-xs font-mono text-neutral-600 break-all" id="meta-hash">—</dd></div>
        </dl>
    </div>

    <!-- Timeline Summary -->
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
        <h3 class="text-lg font-semibold text-neutral-900 mb-4">Writing Timeline</h3>
        <div id="timeline" class="space-y-3">
            <p class="text-sm text-neutral-500">Loading timeline…</p>
        </div>
    </div>

    <!-- AI Interaction Log -->
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
        <h3 class="text-lg font-semibold text-neutral-900 mb-4">AI Interaction Log</h3>
        <div id="ai-log" class="space-y-2">
            <p class="text-sm text-neutral-500">Loading…</p>
        </div>
    </div>

    <!-- Integrity Flags -->
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
        <h3 class="text-lg font-semibold text-neutral-900 mb-4">Integrity Flags</h3>
        <div id="integrity-flags" class="space-y-2">
            <p class="text-sm text-neutral-500">Loading…</p>
        </div>
    </div>
</div>

<script>
(async () => {
    const docId = document.querySelector('[data-doc-id]').dataset.docId;
    try {
        const res = await fetch(`/api/writing/${encodeURIComponent(docId)}/integrity`, { headers: { 'X-CSRF-Token': window.__CSRF__ } });
        const data = await res.json();

        document.getElementById('meta-title').textContent = data.title || 'Untitled';
        document.getElementById('meta-author').textContent = data.author || '—';
        document.getElementById('meta-created').textContent = data.createdAt ? new Date(data.createdAt).toLocaleString() : '—';
        document.getElementById('meta-modified').textContent = data.updatedAt ? new Date(data.updatedAt).toLocaleString() : '—';
        document.getElementById('meta-words').textContent = data.wordCount ?? '—';
        document.getElementById('meta-hash').textContent = data.hash || '—';

        // Timeline
        const timeline = data.timeline || data.snapshots || [];
        document.getElementById('timeline').innerHTML = timeline.length ?
            timeline.map(e => `<div class="flex items-center gap-3 text-sm"><span class="w-36 text-neutral-500 shrink-0">${e.time ? new Date(e.time).toLocaleString() : ''}</span><span class="h-2 w-2 rounded-full bg-primary-500 shrink-0"></span><span class="text-neutral-700">${_esc(e.action || e.event || '')}</span></div>`).join('') :
            '<p class="text-sm text-neutral-400">No timeline data available.</p>';

        // AI Log
        const aiLog = data.aiInteractions || data.aiLog || [];
        document.getElementById('ai-log').innerHTML = aiLog.length ?
            aiLog.map(l => `<div class="rounded-lg bg-neutral-50 border border-neutral-200 p-3 text-xs"><p class="font-medium text-neutral-700">${_esc(l.action || l.type || '')} <span class="text-neutral-400">${l.timestamp ? new Date(l.timestamp).toLocaleString() : ''}</span></p>${l.summary ? `<p class="mt-1 text-neutral-600">${_esc(l.summary)}</p>` : ''}</div>`).join('') :
            '<p class="text-sm text-neutral-400">No AI interactions recorded.</p>';

        // Flags
        const flags = data.flags || [];
        document.getElementById('integrity-flags').innerHTML = flags.length ?
            flags.map(f => `<div class="flex items-center gap-2 rounded-lg p-3 text-sm ${f.severity === 'high' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-amber-50 border border-amber-200 text-amber-700'}"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>${_esc(f.message || f.description || '')}</div>`).join('') :
            '<div class="flex items-center gap-2 rounded-lg bg-green-50 border border-green-200 p-3 text-sm text-green-700"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>No integrity flags. Document looks authentic.</div>';
    } catch {
        document.getElementById('timeline').innerHTML = '<p class="text-sm text-red-600">Failed to load report.</p>';
    }

    // Export
    document.getElementById('export-btn').addEventListener('click', () => { window.print(); });
})();
function _esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
