<?php
/**
 * Quiz Generation Page
 */
$currentPage = 'quizzes';
$pageTitle   = 'Generate Quiz';
$currentUser = $currentUser ?? getCurrentUser();

ob_start();
?>
<div class="mx-auto max-w-3xl space-y-8">
    <div>
        <h2 class="text-2xl font-bold text-neutral-900">Generate a Quiz</h2>
        <p class="mt-1 text-neutral-600">Configure your quiz and let AI create targeted practice questions.</p>
    </div>

    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
        <form id="quiz-form" class="space-y-6" novalidate>
            <!-- Topic Selector -->
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-1">Topics</label>
                <select id="quiz-topic" class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition">
                    <option value="">Loading topics…</option>
                </select>
            </div>

            <!-- Question Count -->
            <div>
                <label for="question-count" class="block text-sm font-medium text-neutral-700 mb-1">Number of Questions: <span id="count-display" class="font-semibold text-primary-600">10</span></label>
                <input type="range" id="question-count" min="5" max="25" value="10" step="1"
                       class="w-full h-2 bg-neutral-200 rounded-lg appearance-none cursor-pointer accent-primary-600"
                       aria-label="Number of questions" aria-valuemin="5" aria-valuemax="25" aria-valuenow="10">
                <div class="flex justify-between text-xs text-neutral-400 mt-1"><span>5</span><span>25</span></div>
            </div>

            <!-- Difficulty Distribution -->
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-1">Difficulty Distribution</label>
                <select id="difficulty-dist" class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition">
                    <option value="balanced">Balanced (easy/medium/hard)</option>
                    <option value="easy-focus">Easy Focus (more foundational)</option>
                    <option value="hard-focus">Hard Focus (more challenging)</option>
                    <option value="progressive">Progressive (easy → hard)</option>
                    <option value="uniform">Uniform (all same level)</option>
                </select>
            </div>

            <!-- Question Types -->
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-2">Question Types</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-2 rounded-lg border border-neutral-200 p-3 cursor-pointer hover:bg-neutral-50 transition">
                        <input type="checkbox" name="q_types" value="multiple_choice" checked class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-neutral-700">Multiple Choice</span>
                    </label>
                    <label class="flex items-center gap-2 rounded-lg border border-neutral-200 p-3 cursor-pointer hover:bg-neutral-50 transition">
                        <input type="checkbox" name="q_types" value="multi_select" checked class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-neutral-700">Multi-Select</span>
                    </label>
                    <label class="flex items-center gap-2 rounded-lg border border-neutral-200 p-3 cursor-pointer hover:bg-neutral-50 transition">
                        <input type="checkbox" name="q_types" value="short_answer" class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-neutral-700">Short Answer</span>
                    </label>
                    <label class="flex items-center gap-2 rounded-lg border border-neutral-200 p-3 cursor-pointer hover:bg-neutral-50 transition">
                        <input type="checkbox" name="q_types" value="numeric_entry" class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-neutral-700">Numeric Entry</span>
                    </label>
                </div>
            </div>

            <!-- Model -->
            <div>
                <label for="quiz-model" class="block text-sm font-medium text-neutral-700 mb-1">AI Model</label>
                <select id="quiz-model" class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition">
                    <option value="gpt-oss-120b" selected>GPT-OSS 120B (Recommended)</option>
                    <option value="llama3.1-8b">Llama 3.1 8B (Faster)</option>
                </select>
            </div>

            <button type="submit" class="w-full rounded-lg bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition">
                Generate Quiz
            </button>
        </form>
    </div>
</div>

<script>
(async () => {
    const select = document.getElementById('quiz-topic');
    const countSlider = document.getElementById('question-count');
    const countDisplay = document.getElementById('count-display');

    countSlider.addEventListener('input', () => { countDisplay.textContent = countSlider.value; countSlider.setAttribute('aria-valuenow', countSlider.value); });

    try {
        const res = await fetch('/api/topics');
        const data = await res.json();
        const topics = Array.isArray(data) ? data : data.topics || [];
        select.innerHTML = '<option value="">Select a topic…</option>' + topics.map(t =>
            `<option value="${_esc(t.id || t.name)}">${_esc(t.name)} (${_esc(t.subject || '')})</option>`
        ).join('');
    } catch {}

    document.getElementById('quiz-form').addEventListener('submit', (e) => {
        e.preventDefault();
        const types = [...document.querySelectorAll('input[name="q_types"]:checked')].map(c => c.value);
        if (!types.length) { window.showToast?.('Select at least one question type.', 'warning'); return; }

        const payload = {
            topic: select.value,
            questionCount: parseInt(countSlider.value),
            difficultyDistribution: document.getElementById('difficulty-dist').value,
            questionTypes: types,
            model: document.getElementById('quiz-model').value,
        };

        const modal = document.getElementById('ai-modal');
        document.getElementById('ai-modal-preview').textContent = JSON.stringify(payload, null, 2);
        modal.classList.remove('hidden');

        const sendBtn = document.getElementById('ai-modal-send');
        const handler = async () => {
            sendBtn.removeEventListener('click', handler);
            modal.classList.add('hidden');
            try {
                const res = await fetch('/api/quizzes/generate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.__CSRF__ },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.error) { window.showToast?.(data.error, 'error'); return; }
                window.location.href = '/quizzes/take/' + (data.id || data.quizId);
            } catch { window.showToast?.('Failed to generate quiz.', 'error'); }
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
