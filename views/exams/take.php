<?php
/**
 * Exam Runner
 * Minimal chrome — the JS test runner handles the exam UI.
 */
$currentPage = 'exams';
$pageTitle   = 'Exam';
$currentUser = $currentUser ?? getCurrentUser();
$examId      = $pageParams['id'] ?? '';

ob_start();
?>
<div id="exam-runner" class="relative" data-exam-id="<?= htmlspecialchars($examId) ?>">
    <!-- Timer -->
    <div class="sticky top-0 z-10 flex items-center justify-between bg-white border-b border-neutral-200 px-4 py-3 -mx-4 sm:-mx-6 lg:-mx-8 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4">
            <h2 id="exam-section-title" class="text-lg font-semibold text-neutral-900">Loading…</h2>
            <span id="exam-progress" class="text-sm text-neutral-500"></span>
        </div>
        <div class="flex items-center gap-3">
            <div id="exam-timer" class="flex items-center gap-2 rounded-lg bg-neutral-900 px-4 py-2 text-sm font-mono text-white">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <span id="exam-time-display">--:--</span>
            </div>
            <button id="exam-pause-btn" type="button" class="rounded-lg border border-neutral-300 px-3 py-2 text-sm text-neutral-700 hover:bg-neutral-50 transition" aria-label="Pause exam">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5"/></svg>
            </button>
            <button id="exam-fullscreen-btn" type="button" class="rounded-lg border border-neutral-300 px-3 py-2 text-sm text-neutral-700 hover:bg-neutral-50 transition" aria-label="Toggle fullscreen">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/></svg>
            </button>
        </div>
    </div>

    <!-- Section Start Page -->
    <div id="section-start" class="hidden py-16 text-center">
        <h3 id="section-start-title" class="text-2xl font-bold text-neutral-900"></h3>
        <p id="section-start-info" class="mt-4 text-neutral-600"></p>
        <button id="section-start-btn" type="button" class="mt-8 rounded-lg bg-primary-600 px-8 py-3 text-sm font-semibold text-white shadow hover:bg-primary-700 transition">Begin Section</button>
    </div>

    <!-- Question Area -->
    <div id="exam-question-area" class="hidden py-6">
        <div class="flex gap-6">
            <div class="flex-1">
                <div class="rounded-2xl bg-white p-6 sm:p-8 shadow-sm ring-1 ring-neutral-200 min-h-[350px]">
                    <div class="flex items-center gap-2 text-sm text-neutral-500 mb-4">
                        <span id="exam-q-number"></span>
                        <span id="exam-q-type" class="rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs"></span>
                    </div>
                    <div id="exam-q-stem" class="text-neutral-900 leading-relaxed mb-6"></div>
                    <div id="exam-q-choices" class="space-y-2"></div>
                    <div id="exam-q-input" class="hidden">
                        <input type="text" id="exam-q-text-input" class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition" placeholder="Type your answer…" aria-label="Your answer">
                    </div>
                </div>
                <div class="flex items-center justify-between mt-4">
                    <button id="exam-prev" type="button" class="rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 transition disabled:opacity-50" disabled>← Previous</button>
                    <button id="exam-next" type="button" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition">Next →</button>
                    <button id="exam-finish-section" type="button" class="hidden rounded-lg bg-green-600 px-6 py-2 text-sm font-semibold text-white hover:bg-green-700 transition">Finish Section</button>
                </div>
            </div>

            <!-- Navigation Panel -->
            <div class="hidden lg:block w-48 shrink-0">
                <div class="sticky top-24 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-200">
                    <h3 class="text-sm font-semibold text-neutral-700 mb-3">Questions</h3>
                    <div id="exam-q-nav" class="grid grid-cols-5 gap-2" role="navigation" aria-label="Question navigation"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pause Overlay -->
    <div id="exam-pause-overlay" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-neutral-900/80 backdrop-blur-sm">
        <div class="rounded-2xl bg-white p-8 text-center shadow-2xl max-w-sm">
            <svg class="mx-auto h-12 w-12 text-primary-600 mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5"/></svg>
            <h3 class="text-xl font-bold text-neutral-900">Exam Paused</h3>
            <p class="mt-2 text-sm text-neutral-600">Timer is stopped. Click below to resume.</p>
            <button id="exam-resume-btn" type="button" class="mt-6 rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 transition">Resume Exam</button>
        </div>
    </div>
</div>

<script>
// Exam runner is controlled by /js/app.js — this provides the HTML structure.
// Fullscreen toggle
document.getElementById('exam-fullscreen-btn')?.addEventListener('click', () => {
    if (!document.fullscreenElement) { document.documentElement.requestFullscreen?.(); }
    else { document.exitFullscreen?.(); }
});

// Pause/Resume
document.getElementById('exam-pause-btn')?.addEventListener('click', () => {
    document.getElementById('exam-pause-overlay').classList.remove('hidden');
});
document.getElementById('exam-resume-btn')?.addEventListener('click', () => {
    document.getElementById('exam-pause-overlay').classList.add('hidden');
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
