<?php
/**
 * Exam Builder
 */
$currentPage = 'exams';
$pageTitle   = 'Exam Builder';
$currentUser = $currentUser ?? getCurrentUser();

ob_start();
?>
<div class="mx-auto max-w-3xl space-y-8">
    <div>
        <h2 class="text-2xl font-bold text-neutral-900">Build an Exam</h2>
        <p class="mt-1 text-neutral-600">Configure your practice exam and generate sections.</p>
    </div>

    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
        <form id="exam-form" class="space-y-6" novalidate>
            <!-- Exam Type -->
            <div>
                <label for="exam-type" class="block text-sm font-medium text-neutral-700 mb-1">Exam Type</label>
                <select id="exam-type" class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition">
                    <option value="sat">SAT</option>
                    <option value="act">ACT</option>
                    <option value="preact">PreACT</option>
                    <option value="mca">MCA</option>
                    <option value="topic">Topic-Based (Custom)</option>
                </select>
            </div>

            <!-- MCA Grade Level -->
            <div id="mca-grade-group" class="hidden">
                <label for="mca-grade" class="block text-sm font-medium text-neutral-700 mb-1">Grade Level</label>
                <select id="mca-grade" class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition">
                    <option value="3">Grade 3</option>
                    <option value="4">Grade 4</option>
                    <option value="5">Grade 5</option>
                    <option value="6">Grade 6</option>
                    <option value="7">Grade 7</option>
                    <option value="8" selected>Grade 8</option>
                    <option value="10">Grade 10 (Reading)</option>
                    <option value="11">Grade 11 (Math/Science)</option>
                </select>
            </div>

            <!-- Topic Selection (for topic-based) -->
            <div id="topic-group" class="hidden">
                <label class="block text-sm font-medium text-neutral-700 mb-1">Topics</label>
                <select id="exam-topics" multiple class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition h-32" aria-label="Select topics for exam">
                </select>
                <p class="mt-1 text-xs text-neutral-500">Hold Ctrl/Cmd to select multiple topics.</p>
            </div>

            <!-- Section Preview -->
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-2">Exam Sections</label>
                <div id="sections-preview" class="space-y-2">
                    <div class="rounded-lg bg-neutral-50 border border-neutral-200 p-4 text-sm text-neutral-600">Select an exam type to see section details.</div>
                </div>
            </div>

            <!-- AI Note -->
            <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">
                <strong>Note:</strong> Generating a full exam will make <span id="ai-call-count">multiple</span> separate AI calls (one per section). This may take a minute.
            </div>

            <button type="submit" class="w-full rounded-lg bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition">
                Generate Exam
            </button>
        </form>
    </div>
</div>

<script>
(() => {
    const typeSelect = document.getElementById('exam-type');
    const mcaGroup = document.getElementById('mca-grade-group');
    const topicGroup = document.getElementById('topic-group');
    const sectionsPreview = document.getElementById('sections-preview');
    const callCount = document.getElementById('ai-call-count');

    const examConfigs = {
        sat: { sections: [{ name: 'Reading & Writing Module 1', time: 32, q: 27 }, { name: 'Reading & Writing Module 2', time: 32, q: 27 }, { name: 'Math Module 1', time: 35, q: 22 }, { name: 'Math Module 2', time: 35, q: 22 }] },
        act: { sections: [{ name: 'English', time: 45, q: 75 }, { name: 'Mathematics', time: 60, q: 60 }, { name: 'Reading', time: 35, q: 40 }, { name: 'Science', time: 35, q: 40 }] },
        preact: { sections: [{ name: 'English', time: 30, q: 40 }, { name: 'Mathematics', time: 40, q: 36 }, { name: 'Reading', time: 25, q: 25 }, { name: 'Science', time: 25, q: 25 }] },
        mca: { sections: [{ name: 'Section 1', time: 60, q: 30 }, { name: 'Section 2', time: 60, q: 30 }] },
        topic: { sections: [{ name: 'Custom Section', time: 45, q: 30 }] },
    };

    // Pre-fill from URL
    const params = new URLSearchParams(window.location.search);
    if (params.get('type')) typeSelect.value = params.get('type');

    function updateUI() {
        const type = typeSelect.value;
        mcaGroup.classList.toggle('hidden', type !== 'mca');
        topicGroup.classList.toggle('hidden', type !== 'topic');

        const config = examConfigs[type] || examConfigs.topic;
        callCount.textContent = config.sections.length;
        sectionsPreview.innerHTML = config.sections.map(s => `
            <div class="flex items-center justify-between rounded-lg bg-neutral-50 border border-neutral-200 p-3">
                <span class="text-sm font-medium text-neutral-700">${s.name}</span>
                <span class="text-xs text-neutral-500">${s.q} questions · ${s.time} min</span>
            </div>
        `).join('');
    }

    typeSelect.addEventListener('change', updateUI);
    updateUI();

    // Load topics for topic-based
    (async () => {
        try {
            const res = await fetch('/api/topics');
            const data = await res.json();
            const topics = Array.isArray(data) ? data : data.topics || [];
            document.getElementById('exam-topics').innerHTML = topics.map(t =>
                `<option value="${t.id || t.name}">${t.name} (${t.subject || ''})</option>`
            ).join('');
        } catch {}
    })();

    document.getElementById('exam-form').addEventListener('submit', (e) => {
        e.preventDefault();
        const type = typeSelect.value;
        const payload = { type };
        if (type === 'mca') payload.grade = document.getElementById('mca-grade').value;
        if (type === 'topic') payload.topics = [...document.getElementById('exam-topics').selectedOptions].map(o => o.value);

        const config = examConfigs[type];
        payload.sections = config.sections;

        const modal = document.getElementById('ai-modal');
        document.getElementById('ai-modal-preview').textContent = JSON.stringify(payload, null, 2);
        modal.classList.remove('hidden');

        const sendBtn = document.getElementById('ai-modal-send');
        const handler = async () => {
            sendBtn.removeEventListener('click', handler);
            modal.classList.add('hidden');
            window.showToast?.('Generating exam sections…', 'info');

            try {
                const res = await fetch('/api/exams/generate-section', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.__CSRF__ },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.error) { window.showToast?.(data.error, 'error'); return; }
                window.location.href = '/exams/take/' + (data.id || data.examId);
            } catch { window.showToast?.('Failed to generate exam.', 'error'); }
        };
        sendBtn.addEventListener('click', handler);
    });
})();
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
