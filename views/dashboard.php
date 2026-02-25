<?php
/**
 * Dashboard
 */
$currentPage = 'dashboard';
$pageTitle   = 'Dashboard';
$currentUser = $currentUser ?? getCurrentUser();

ob_start();
?>
<div class="space-y-8">
    <!-- Welcome -->
    <div>
        <h2 class="text-2xl font-bold text-neutral-900">Welcome back, <?= htmlspecialchars($currentUser['username'] ?? 'Learner') ?>!</h2>
        <p class="mt-1 text-neutral-600">Here's your learning overview.</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" id="stats-grid">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-100 text-primary-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347"/></svg>
                </div>
                <div>
                    <p class="text-sm text-neutral-500">Lessons Generated</p>
                    <p class="text-2xl font-bold text-neutral-900" id="stat-lessons">—</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08"/></svg>
                </div>
                <div>
                    <p class="text-sm text-neutral-500">Quizzes Taken</p>
                    <p class="text-2xl font-bold text-neutral-900" id="stat-quizzes">—</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25"/></svg>
                </div>
                <div>
                    <p class="text-sm text-neutral-500">Exams Completed</p>
                    <p class="text-2xl font-bold text-neutral-900" id="stat-exams">—</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-neutral-500">Documents Written</p>
                    <p class="text-2xl font-bold text-neutral-900" id="stat-docs">—</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions + Recent Activity -->
    <div class="grid gap-8 lg:grid-cols-3">
        <!-- Quick Actions -->
        <div class="lg:col-span-1">
            <h3 class="text-lg font-semibold text-neutral-900 mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="/lessons/generate" class="flex items-center gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-neutral-200 hover:ring-primary-300 hover:shadow-md transition group">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-100 text-primary-600 group-hover:bg-primary-600 group-hover:text-white transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-neutral-900">Generate Lesson</p>
                        <p class="text-xs text-neutral-500">Create an AI-powered lesson</p>
                    </div>
                </a>
                <a href="/quizzes/generate" class="flex items-center gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-neutral-200 hover:ring-emerald-300 hover:shadow-md transition group">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-neutral-900">Take Quiz</p>
                        <p class="text-xs text-neutral-500">Test your knowledge</p>
                    </div>
                </a>
                <a href="/exams/builder" class="flex items-center gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-neutral-200 hover:ring-amber-300 hover:shadow-md transition group">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-neutral-900">Start Exam</p>
                        <p class="text-xs text-neutral-500">Full practice exams</p>
                    </div>
                </a>
                <a href="/writing" class="flex items-center gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-neutral-200 hover:ring-rose-300 hover:shadow-md transition group">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-100 text-rose-600 group-hover:bg-rose-600 group-hover:text-white transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-neutral-900">Open Writing Studio</p>
                        <p class="text-xs text-neutral-500">Write and get feedback</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="lg:col-span-2">
            <h3 class="text-lg font-semibold text-neutral-900 mb-4">Recent Activity</h3>
            <div id="recent-activity" class="rounded-2xl bg-white shadow-sm ring-1 ring-neutral-200 divide-y divide-neutral-100">
                <!-- Empty state -->
                <div id="activity-empty" class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <p class="mt-3 text-sm text-neutral-500">No recent activity yet. Start by generating a lesson!</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress -->
    <div>
        <h3 class="text-lg font-semibold text-neutral-900 mb-4">Learning Progress</h3>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-neutral-700">Math</p>
                    <span class="text-xs text-neutral-500" id="progress-math-pct">0%</span>
                </div>
                <div class="h-2 rounded-full bg-neutral-200"><div id="progress-math" class="h-2 rounded-full bg-primary-500 transition-all" style="width:0%"></div></div>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-neutral-700">Reading & Writing</p>
                    <span class="text-xs text-neutral-500" id="progress-rw-pct">0%</span>
                </div>
                <div class="h-2 rounded-full bg-neutral-200"><div id="progress-rw" class="h-2 rounded-full bg-emerald-500 transition-all" style="width:0%"></div></div>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-neutral-700">Science</p>
                    <span class="text-xs text-neutral-500" id="progress-sci-pct">0%</span>
                </div>
                <div class="h-2 rounded-full bg-neutral-200"><div id="progress-sci" class="h-2 rounded-full bg-amber-500 transition-all" style="width:0%"></div></div>
            </div>
        </div>
    </div>
</div>

<script>
(async () => {
    try {
        const res = await fetch('/api/dashboard/stats', { headers: { 'X-CSRF-Token': window.__CSRF__ } });
        if (res.ok) {
            const d = await res.json();
            document.getElementById('stat-lessons').textContent = d.lessons ?? 0;
            document.getElementById('stat-quizzes').textContent = d.quizzes ?? 0;
            document.getElementById('stat-exams').textContent = d.exams ?? 0;
            document.getElementById('stat-docs').textContent = d.documents ?? 0;
        }
    } catch {}
})();
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layout.php';
?>
