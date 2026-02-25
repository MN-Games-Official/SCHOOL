<?php
/**
 * Share View Page
 */
$guestLayout = true;
$pageTitle   = 'Shared Report';
$shareId     = $pageParams['id'] ?? '';

ob_start();
?>
<div class="mx-auto max-w-3xl px-4 py-12">
    <!-- Password Entry -->
    <div id="share-auth" class="text-center">
        <div class="rounded-2xl bg-white p-8 shadow-lg ring-1 ring-neutral-200 max-w-md mx-auto">
            <svg class="mx-auto h-12 w-12 text-primary-600 mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
            <h2 class="text-xl font-bold text-neutral-900 mb-2">This report is protected</h2>
            <p class="text-sm text-neutral-600 mb-6">Enter the password to view this integrity report.</p>
            <form id="share-form" class="space-y-4" novalidate>
                <input type="password" id="share-password" required
                       class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:bg-white transition"
                       placeholder="Enter password" aria-label="Password" aria-required="true">
                <div id="share-error" class="hidden rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700" role="alert"></div>
                <button type="submit" class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-primary-700 transition">View Report</button>
            </form>
        </div>
    </div>

    <!-- Report Content (hidden until verified) -->
    <div id="share-content" class="hidden space-y-6">
        <h2 class="text-2xl font-bold text-neutral-900">Integrity Report</h2>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
            <h3 class="text-lg font-semibold text-neutral-900 mb-4">Document Info</h3>
            <dl id="share-metadata" class="grid gap-4 sm:grid-cols-2"></dl>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
            <h3 class="text-lg font-semibold text-neutral-900 mb-4">Timeline</h3>
            <div id="share-timeline"></div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
            <h3 class="text-lg font-semibold text-neutral-900 mb-4">AI Interactions</h3>
            <div id="share-ai-log"></div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
            <h3 class="text-lg font-semibold text-neutral-900 mb-4">Integrity Flags</h3>
            <div id="share-flags"></div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
            <h3 class="text-lg font-semibold text-neutral-900 mb-4">Verification</h3>
            <p class="text-xs font-mono text-neutral-600 break-all" id="share-hash"></p>
        </div>
    </div>
</div>

<script>
const shareId = <?= json_encode($shareId) ?>;

document.getElementById('share-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const errorEl = document.getElementById('share-error');
    errorEl.classList.add('hidden');
    const password = document.getElementById('share-password').value;

    try {
        const res = await fetch(`/api/shares/${encodeURIComponent(shareId)}`, {
            headers: { 'Authorization': 'Bearer ' + password }
        });
        const data = await res.json();
        if (data.error) { errorEl.textContent = data.error; errorEl.classList.remove('hidden'); return; }

        // Show report
        document.getElementById('share-auth').classList.add('hidden');
        document.getElementById('share-content').classList.remove('hidden');

        // Metadata
        const meta = document.getElementById('share-metadata');
        meta.innerHTML = Object.entries({ Title: data.title, Author: data.author, Created: data.createdAt ? new Date(data.createdAt).toLocaleString() : '—', Words: data.wordCount ?? '—' })
            .map(([k, v]) => `<div><dt class="text-sm text-neutral-500">${k}</dt><dd class="text-sm font-medium text-neutral-900">${_esc(String(v))}</dd></div>`).join('');

        // Timeline
        const timeline = data.timeline || [];
        document.getElementById('share-timeline').innerHTML = timeline.length ?
            timeline.map(e => `<div class="flex items-center gap-3 text-sm mb-2"><span class="w-36 text-neutral-500 shrink-0">${e.time ? new Date(e.time).toLocaleString() : ''}</span><span class="h-2 w-2 rounded-full bg-primary-500 shrink-0"></span><span class="text-neutral-700">${_esc(e.action || '')}</span></div>`).join('') :
            '<p class="text-sm text-neutral-400">No timeline data.</p>';

        // AI log
        const aiLog = data.aiInteractions || [];
        document.getElementById('share-ai-log').innerHTML = aiLog.length ?
            aiLog.map(l => `<div class="rounded-lg bg-neutral-50 border border-neutral-200 p-3 text-xs mb-2"><strong>${_esc(l.action || '')}</strong> ${l.timestamp ? new Date(l.timestamp).toLocaleString() : ''}</div>`).join('') :
            '<p class="text-sm text-neutral-400">No AI interactions.</p>';

        // Flags
        const flags = data.flags || [];
        document.getElementById('share-flags').innerHTML = flags.length ?
            flags.map(f => `<div class="flex items-center gap-2 rounded-lg p-3 text-sm mb-2 bg-amber-50 border border-amber-200 text-amber-700">${_esc(f.message || '')}</div>`).join('') :
            '<div class="rounded-lg bg-green-50 border border-green-200 p-3 text-sm text-green-700">No integrity flags.</div>';

        document.getElementById('share-hash').textContent = data.hash || '—';
    } catch {
        errorEl.textContent = 'Network error. Please try again.';
        errorEl.classList.remove('hidden');
    }
});
function _esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layout.php';
?>
