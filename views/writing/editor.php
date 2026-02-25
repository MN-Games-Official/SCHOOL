<?php
/**
 * Writing Studio Editor
 * Three-column layout: Outline | Editor + Preview | Issues + Integrity
 */
$currentPage = 'writing';
$pageTitle   = 'Writing Editor';
$currentUser = $currentUser ?? getCurrentUser();
$docId       = $pageParams['id'] ?? '';

ob_start();
?>
<div class="flex h-[calc(100vh-8rem)] -m-4 sm:-m-6 lg:-m-8" id="writing-app" data-doc-id="<?= htmlspecialchars($docId) ?>">
    <!-- Left: Outline Panel -->
    <div class="hidden md:flex md:flex-col w-56 bg-white border-r border-neutral-200 shrink-0">
        <div class="p-4 border-b border-neutral-200">
            <h3 class="text-sm font-semibold text-neutral-700">Outline</h3>
        </div>
        <div id="doc-outline" class="flex-1 overflow-y-auto p-3 space-y-1">
            <p class="text-xs text-neutral-400 p-2">Headings will appear here…</p>
        </div>
    </div>

    <!-- Center: Editor -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Toolbar -->
        <div class="flex items-center gap-1 border-b border-neutral-200 bg-white px-4 py-2 overflow-x-auto" role="toolbar" aria-label="Formatting toolbar">
            <button type="button" class="toolbar-btn p-2 rounded hover:bg-neutral-100 text-neutral-600 transition" data-action="heading" title="Heading" aria-label="Heading">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v18M19 3v18M5 12h14"/></svg>
            </button>
            <button type="button" class="toolbar-btn p-2 rounded hover:bg-neutral-100 text-neutral-600 transition" data-action="bold" title="Bold" aria-label="Bold">
                <span class="text-sm font-bold">B</span>
            </button>
            <button type="button" class="toolbar-btn p-2 rounded hover:bg-neutral-100 text-neutral-600 transition" data-action="italic" title="Italic" aria-label="Italic">
                <span class="text-sm italic">I</span>
            </button>
            <div class="w-px h-5 bg-neutral-200 mx-1"></div>
            <button type="button" class="toolbar-btn p-2 rounded hover:bg-neutral-100 text-neutral-600 transition" data-action="ul" title="Bullet List" aria-label="Bullet list">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
            </button>
            <button type="button" class="toolbar-btn p-2 rounded hover:bg-neutral-100 text-neutral-600 transition" data-action="ol" title="Numbered List" aria-label="Numbered list">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.242 5.992h12m-12 6.003h12m-12 5.999h12M4.117 7.495v-3.75H2.99m1.125 3.75H2.99m1.125 0H4.75m-1.506 7.502c.253-.212.577-.375.913-.375.7 0 1.27.6 1.27 1.335 0 .592-.287.984-.714 1.377l-1.89 1.726h2.654M3.244 18.997V21.5m1.506-5.753H3.244"/></svg>
            </button>
            <button type="button" class="toolbar-btn p-2 rounded hover:bg-neutral-100 text-neutral-600 transition" data-action="quote" title="Quote" aria-label="Block quote">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-5.25 6 3-3h5.25a2.25 2.25 0 0 0 2.25-2.25v-6a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6v6c0 1.243 1.007 2.25 2.25 2.25Z"/></svg>
            </button>
            <button type="button" class="toolbar-btn p-2 rounded hover:bg-neutral-100 text-neutral-600 transition" data-action="code" title="Code" aria-label="Code block">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5"/></svg>
            </button>
            <button type="button" class="toolbar-btn p-2 rounded hover:bg-neutral-100 text-neutral-600 transition" data-action="equation" title="Equation" aria-label="Math equation">
                <span class="text-sm font-mono">∑</span>
            </button>
            <button type="button" class="toolbar-btn p-2 rounded hover:bg-neutral-100 text-neutral-600 transition" data-action="table" title="Table" aria-label="Insert table">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M10.875 12h2.25m-2.25 0a1.125 1.125 0 0 1-1.125 1.125M13.125 12c.621 0 1.125.504 1.125 1.125m-2.25 0v3.375m0-3.375c0 .621-.504 1.125-1.125 1.125M12 15.375c0-.621.504-1.125 1.125-1.125"/></svg>
            </button>
            <div class="flex-1"></div>
            <button type="button" id="toggle-preview" class="p-2 rounded hover:bg-neutral-100 text-neutral-500 transition text-xs font-medium" title="Toggle preview" aria-label="Toggle live preview">Preview</button>
        </div>

        <!-- Editor + Preview -->
        <div class="flex-1 flex overflow-hidden">
            <!-- Textarea -->
            <div class="flex-1 relative" id="editor-pane">
                <textarea id="editor-textarea" class="w-full h-full resize-none border-none bg-neutral-50 p-6 text-sm text-neutral-900 font-mono leading-relaxed focus:outline-none focus:bg-white transition" placeholder="Start writing in Markdown…" aria-label="Document editor" spellcheck="true"></textarea>
            </div>
            <!-- Preview -->
            <div id="preview-pane" class="hidden flex-1 border-l border-neutral-200 overflow-y-auto bg-white p-6">
                <div id="preview-content" class="prose prose-neutral max-w-none text-sm"></div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between border-t border-neutral-200 bg-white px-4 py-2 text-xs text-neutral-500">
            <div class="flex items-center gap-4">
                <span id="word-count">0 words</span>
                <span id="char-count">0 characters</span>
            </div>
            <div class="flex items-center gap-2">
                <span id="save-status" class="text-neutral-400">Unsaved</span>
            </div>
        </div>
    </div>

    <!-- Right: Issues + Integrity Panel -->
    <div class="hidden lg:flex lg:flex-col w-72 bg-white border-l border-neutral-200 shrink-0">
        <div class="border-b border-neutral-200">
            <div class="flex">
                <button type="button" class="flex-1 px-4 py-3 text-sm font-medium text-primary-600 border-b-2 border-primary-600" id="tab-issues" role="tab" aria-selected="true">Issues</button>
                <button type="button" class="flex-1 px-4 py-3 text-sm font-medium text-neutral-500 border-b-2 border-transparent hover:text-neutral-700" id="tab-integrity" role="tab" aria-selected="false">Integrity</button>
            </div>
        </div>

        <!-- Issues content -->
        <div id="panel-issues" class="flex-1 overflow-y-auto p-4 space-y-3">
            <p class="text-xs text-neutral-400">Scan your document to see issues.</p>
        </div>

        <!-- Integrity content -->
        <div id="panel-integrity" class="hidden flex-1 overflow-y-auto p-4 space-y-3">
            <p class="text-xs text-neutral-400">Integrity data will appear after scanning.</p>
        </div>

        <!-- Action buttons -->
        <div class="border-t border-neutral-200 p-4 space-y-2">
            <!-- Rubric selector -->
            <div>
                <label for="rubric-select" class="block text-xs font-medium text-neutral-600 mb-1">Rubric</label>
                <select id="rubric-select" class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-3 py-2 text-xs focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition" aria-label="Select rubric">
                    <option value="general">General Writing</option>
                    <option value="argumentative">Argumentative Essay</option>
                    <option value="narrative">Narrative Writing</option>
                    <option value="expository">Expository Writing</option>
                    <option value="custom">Custom (paste below)</option>
                </select>
            </div>

            <button id="scan-btn" type="button" class="w-full rounded-lg border border-primary-300 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700 hover:bg-primary-100 transition">
                Scan Document
            </button>
            <button id="grade-btn" type="button" class="w-full rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition">
                Grade Document
            </button>
        </div>
    </div>
</div>

<script>
(async () => {
    const docId = document.getElementById('writing-app').dataset.docId;
    const textarea = document.getElementById('editor-textarea');
    const wordCount = document.getElementById('word-count');
    const charCount = document.getElementById('char-count');
    const saveStatus = document.getElementById('save-status');
    const outline = document.getElementById('doc-outline');
    const previewContent = document.getElementById('preview-content');
    let showPreview = false, saveTimer = null;

    // Load document
    try {
        const res = await fetch(`/api/writing/${encodeURIComponent(docId)}`, { headers: { 'X-CSRF-Token': window.__CSRF__ } });
        const data = await res.json();
        if (data.content != null) textarea.value = data.content;
        updateCounts();
        updateOutline();
    } catch {}

    // Counts
    function updateCounts() {
        const text = textarea.value;
        const words = text.trim() ? text.trim().split(/\s+/).length : 0;
        wordCount.textContent = `${words} word${words !== 1 ? 's' : ''}`;
        charCount.textContent = `${text.length} characters`;
    }

    // Outline
    function updateOutline() {
        const headings = textarea.value.match(/^#{1,3}\s+.+$/gm) || [];
        if (!headings.length) { outline.innerHTML = '<p class="text-xs text-neutral-400 p-2">No headings found.</p>'; return; }
        outline.innerHTML = headings.map(h => {
            const level = h.match(/^(#+)/)[1].length;
            const text = h.replace(/^#+\s*/, '');
            return `<button type="button" class="block w-full text-left px-2 py-1 text-xs rounded hover:bg-neutral-100 transition truncate" style="padding-left:${level * 8}px" title="${text}">${text}</button>`;
        }).join('');
    }

    textarea.addEventListener('input', () => {
        updateCounts();
        updateOutline();
        saveStatus.textContent = 'Unsaved';
        if (showPreview) window.renderContent?.(textarea.value, previewContent);

        clearTimeout(saveTimer);
        saveTimer = setTimeout(async () => {
            saveStatus.textContent = 'Saving…';
            try {
                await fetch(`/api/writing/${encodeURIComponent(docId)}/save`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.__CSRF__ },
                    body: JSON.stringify({ content: textarea.value })
                });
                saveStatus.textContent = 'Saved';
            } catch { saveStatus.textContent = 'Save failed'; }
        }, 1500);
    });

    // Preview toggle
    document.getElementById('toggle-preview').addEventListener('click', () => {
        showPreview = !showPreview;
        document.getElementById('preview-pane').classList.toggle('hidden', !showPreview);
        if (showPreview) window.renderContent?.(textarea.value, previewContent);
    });

    // Panel tabs
    document.getElementById('tab-issues').addEventListener('click', () => {
        document.getElementById('panel-issues').classList.remove('hidden');
        document.getElementById('panel-integrity').classList.add('hidden');
        document.getElementById('tab-issues').className = 'flex-1 px-4 py-3 text-sm font-medium text-primary-600 border-b-2 border-primary-600';
        document.getElementById('tab-integrity').className = 'flex-1 px-4 py-3 text-sm font-medium text-neutral-500 border-b-2 border-transparent hover:text-neutral-700';
    });
    document.getElementById('tab-integrity').addEventListener('click', () => {
        document.getElementById('panel-issues').classList.add('hidden');
        document.getElementById('panel-integrity').classList.remove('hidden');
        document.getElementById('tab-integrity').className = 'flex-1 px-4 py-3 text-sm font-medium text-primary-600 border-b-2 border-primary-600';
        document.getElementById('tab-issues').className = 'flex-1 px-4 py-3 text-sm font-medium text-neutral-500 border-b-2 border-transparent hover:text-neutral-700';
    });

    // Toolbar
    document.querySelectorAll('.toolbar-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const action = btn.dataset.action;
            const start = textarea.selectionStart, end = textarea.selectionEnd;
            const selected = textarea.value.substring(start, end);
            const inserts = {
                heading: { before: '## ', after: '' },
                bold: { before: '**', after: '**' },
                italic: { before: '_', after: '_' },
                ul: { before: '- ', after: '' },
                ol: { before: '1. ', after: '' },
                quote: { before: '> ', after: '' },
                code: { before: '```\n', after: '\n```' },
                equation: { before: '$', after: '$' },
                table: { before: '| Header | Header |\n|--------|--------|\n| Cell   | Cell   |', after: '' },
            };
            const ins = inserts[action];
            if (!ins) return;
            textarea.value = textarea.value.substring(0, start) + ins.before + selected + ins.after + textarea.value.substring(end);
            textarea.focus();
            textarea.selectionStart = textarea.selectionEnd = start + ins.before.length + selected.length + ins.after.length;
            textarea.dispatchEvent(new Event('input'));
        });
    });

    // Scan
    document.getElementById('scan-btn').addEventListener('click', () => {
        const payload = { documentId: docId, content: textarea.value, rubric: document.getElementById('rubric-select').value };
        const modal = document.getElementById('ai-modal');
        document.getElementById('ai-modal-preview').textContent = JSON.stringify({ action: 'scan', ...payload }, null, 2);
        modal.classList.remove('hidden');
        const sendBtn = document.getElementById('ai-modal-send');
        const handler = async () => {
            sendBtn.removeEventListener('click', handler);
            modal.classList.add('hidden');
            try {
                const res = await fetch(`/api/writing/${encodeURIComponent(docId)}/scan`, {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.__CSRF__ },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                const issues = data.issues || [];
                document.getElementById('panel-issues').innerHTML = issues.length ?
                    issues.map(iss => `<div class="rounded-lg border p-3 text-xs ${iss.severity === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-amber-200 bg-amber-50 text-amber-700'}"><strong>${iss.type || 'Issue'}:</strong> ${iss.message || ''}</div>`).join('') :
                    '<p class="text-xs text-green-600">No issues found!</p>';
            } catch { window.showToast?.('Scan failed.', 'error'); }
        };
        sendBtn.addEventListener('click', handler);
    });

    // Grade
    document.getElementById('grade-btn').addEventListener('click', () => {
        const payload = { documentId: docId, content: textarea.value, rubric: document.getElementById('rubric-select').value };
        const modal = document.getElementById('ai-modal');
        document.getElementById('ai-modal-preview').textContent = JSON.stringify({ action: 'grade', ...payload }, null, 2);
        modal.classList.remove('hidden');
        const sendBtn = document.getElementById('ai-modal-send');
        const handler = async () => {
            sendBtn.removeEventListener('click', handler);
            modal.classList.add('hidden');
            try {
                const res = await fetch(`/api/writing/${encodeURIComponent(docId)}/grade`, {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.__CSRF__ },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                window.showToast?.(`Grade: ${data.grade || data.score || 'Complete'}`, 'success');
            } catch { window.showToast?.('Grading failed.', 'error'); }
        };
        sendBtn.addEventListener('click', handler);
    });
})();
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
