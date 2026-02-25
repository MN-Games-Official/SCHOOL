<?php
/**
 * Lesson Generation Page
 */
$currentPage = 'lessons';
$pageTitle   = 'Generate Lesson';
$currentUser = $currentUser ?? getCurrentUser();

ob_start();
?>
<div class="mx-auto max-w-4xl space-y-8">
    <!-- Header -->
    <div>
        <h2 class="text-2xl font-bold text-neutral-900">Generate a Lesson</h2>
        <p class="mt-1 text-neutral-600">Configure your lesson parameters and let AI create personalized content.</p>
    </div>

    <!-- Config Form -->
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
        <form id="lesson-form" class="space-y-6" novalidate>
            <!-- Topic Selector -->
            <div>
                <label for="topic-select" class="block text-sm font-medium text-neutral-700 mb-1">Topics</label>
                <div class="relative">
                    <input type="text" id="topic-search-input" placeholder="Search and select topics…"
                           class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm placeholder-neutral-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:bg-white transition"
                           aria-label="Search topics" autocomplete="off">
                    <div id="topic-dropdown" class="hidden absolute z-10 mt-1 w-full max-h-48 overflow-y-auto rounded-lg bg-white border border-neutral-200 shadow-lg"></div>
                </div>
                <div id="selected-topics" class="flex flex-wrap gap-2 mt-2"></div>
            </div>

            <!-- Difficulty -->
            <div>
                <label for="difficulty" class="block text-sm font-medium text-neutral-700 mb-1">Difficulty Level</label>
                <select id="difficulty" class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition">
                    <option value="1">1 — Foundational</option>
                    <option value="2">2 — Basic</option>
                    <option value="3">3 — Elementary</option>
                    <option value="4">4 — Intermediate</option>
                    <option value="5" selected>5 — Proficient</option>
                    <option value="6">6 — Advanced</option>
                    <option value="7">7 — Expert</option>
                    <option value="8">8 — Master</option>
                    <option value="9">9 — Scholar</option>
                    <option value="10">10 — Genius</option>
                </select>
            </div>

            <!-- Lesson Length -->
            <div>
                <label for="length" class="block text-sm font-medium text-neutral-700 mb-1">Lesson Length</label>
                <select id="length" class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition">
                    <option value="brief">Brief (~5 min read)</option>
                    <option value="short">Short (~10 min read)</option>
                    <option value="medium" selected>Medium (~20 min read)</option>
                    <option value="long">Long (~30 min read)</option>
                    <option value="comprehensive">Comprehensive (~45+ min read)</option>
                </select>
            </div>

            <!-- Model Selector -->
            <div>
                <label for="model" class="block text-sm font-medium text-neutral-700 mb-1">AI Model</label>
                <select id="model" class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition">
                    <option value="gpt-oss-120b" selected>GPT-OSS 120B (Recommended)</option>
                    <option value="llama3.1-8b">Llama 3.1 8B (Faster)</option>
                </select>
                <p class="mt-1 text-xs text-neutral-500">GPT-OSS 120B produces higher quality output. Llama 3.1 8B is faster but less detailed.</p>
            </div>

            <!-- Seed -->
            <div>
                <label for="seed" class="block text-sm font-medium text-neutral-700 mb-1">Seed <span class="text-neutral-400">(optional)</span></label>
                <input type="number" id="seed" placeholder="Auto-generated if empty"
                       class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm placeholder-neutral-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:bg-white transition">
                <p class="mt-1 text-xs text-neutral-500">Use a seed for reproducible lesson generation.</p>
            </div>

            <!-- Generate Button -->
            <button type="submit" id="generate-btn" class="w-full rounded-lg bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition">
                Generate Lesson
            </button>
        </form>
    </div>

    <!-- Generated Lesson Display -->
    <div id="lesson-output" class="hidden">
        <div class="rounded-2xl bg-white p-6 sm:p-8 shadow-sm ring-1 ring-neutral-200">
            <div id="lesson-loading" class="flex justify-center py-12">
                <div class="flex items-center gap-3 text-neutral-500">
                    <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Generating your lesson…
                </div>
            </div>
            <article id="lesson-content" class="prose prose-neutral max-w-none hidden" role="article"></article>
        </div>
    </div>
</div>

<script>
(async () => {
    let allTopics = [];
    let selectedTopics = [];

    // Load topics
    try {
        const res = await fetch('/api/topics');
        const data = await res.json();
        allTopics = Array.isArray(data) ? data : data.topics || [];
    } catch {}

    const searchInput = document.getElementById('topic-search-input');
    const dropdown = document.getElementById('topic-dropdown');
    const selectedEl = document.getElementById('selected-topics');

    // Pre-fill from URL
    const params = new URLSearchParams(window.location.search);
    const preselect = params.get('topic');
    if (preselect) {
        const match = allTopics.find(t => (t.id || t.name) === preselect);
        if (match) { selectedTopics.push(match); renderSelected(); }
    }

    searchInput.addEventListener('focus', () => showDropdown());
    searchInput.addEventListener('input', () => showDropdown());
    document.addEventListener('click', (e) => { if (!e.target.closest('#topic-search-input, #topic-dropdown')) dropdown.classList.add('hidden'); });

    function showDropdown() {
        const q = searchInput.value.toLowerCase();
        const filtered = allTopics.filter(t => !selectedTopics.includes(t) && (!q || t.name?.toLowerCase().includes(q) || t.subject?.toLowerCase().includes(q)));
        if (!filtered.length) { dropdown.classList.add('hidden'); return; }
        dropdown.innerHTML = filtered.slice(0, 20).map((t, i) =>
            `<button type="button" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary-50 transition" data-idx="${i}">${_esc(t.name)} <span class="text-neutral-400 text-xs">${_esc(t.subject || '')}</span></button>`
        ).join('');
        dropdown.classList.remove('hidden');
        dropdown.querySelectorAll('button').forEach((btn, i) => {
            btn.addEventListener('click', () => { selectedTopics.push(filtered[i]); searchInput.value = ''; dropdown.classList.add('hidden'); renderSelected(); });
        });
    }

    function renderSelected() {
        selectedEl.innerHTML = selectedTopics.map((t, i) =>
            `<span class="inline-flex items-center gap-1 rounded-full bg-primary-100 pl-3 pr-1.5 py-1 text-sm text-primary-700">
                ${_esc(t.name)}
                <button type="button" class="ml-1 rounded-full p-0.5 hover:bg-primary-200 transition" data-remove="${i}" aria-label="Remove ${_esc(t.name)}">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </span>`
        ).join('');
        selectedEl.querySelectorAll('[data-remove]').forEach(btn => {
            btn.addEventListener('click', () => { selectedTopics.splice(parseInt(btn.dataset.remove), 1); renderSelected(); });
        });
    }

    // Form submission
    document.getElementById('lesson-form').addEventListener('submit', (e) => {
        e.preventDefault();
        if (!selectedTopics.length) { window.showToast?.('Please select at least one topic.', 'warning'); return; }

        const payload = {
            topics: selectedTopics.map(t => t.id || t.name),
            difficulty: parseInt(document.getElementById('difficulty').value),
            length: document.getElementById('length').value,
            model: document.getElementById('model').value,
            seed: document.getElementById('seed').value ? parseInt(document.getElementById('seed').value) : undefined,
        };

        // Show AI Request Modal
        const modal = document.getElementById('ai-modal');
        const preview = document.getElementById('ai-modal-preview');
        preview.textContent = JSON.stringify(payload, null, 2);
        modal.classList.remove('hidden');

        const sendBtn = document.getElementById('ai-modal-send');
        const handler = async () => {
            sendBtn.removeEventListener('click', handler);
            modal.classList.add('hidden');

            const output = document.getElementById('lesson-output');
            const content = document.getElementById('lesson-content');
            const loadingEl = document.getElementById('lesson-loading');
            output.classList.remove('hidden');
            content.classList.add('hidden');
            loadingEl.classList.remove('hidden');

            try {
                const res = await fetch('/api/lessons/generate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.__CSRF__ },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                loadingEl.classList.add('hidden');
                content.classList.remove('hidden');
                if (data.error) { content.innerHTML = `<p class="text-red-600">${_esc(data.error)}</p>`; return; }
                window.renderContent?.(data.content || data.lesson || JSON.stringify(data), content);
            } catch {
                loadingEl.classList.add('hidden');
                content.classList.remove('hidden');
                content.innerHTML = '<p class="text-red-600">Failed to generate lesson. Please try again.</p>';
            }
        };
        sendBtn.addEventListener('click', handler);
    });
})();
function _esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
