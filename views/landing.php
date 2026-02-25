<?php
/**
 * Landing Page
 */
$guestLayout  = true;
$pageTitle    = 'Welcome';

ob_start();
?>
<!-- Hero -->
<section class="relative overflow-hidden bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900">
    <div class="absolute inset-0 opacity-10">
        <svg class="h-full w-full" viewBox="0 0 800 600" fill="none"><circle cx="400" cy="300" r="300" stroke="white" stroke-width="0.5"/><circle cx="400" cy="300" r="200" stroke="white" stroke-width="0.5"/><circle cx="400" cy="300" r="100" stroke="white" stroke-width="0.5"/></svg>
    </div>
    <div class="relative mx-auto max-w-7xl px-4 py-24 sm:px-6 sm:py-32 lg:px-8 lg:py-40 text-center">
        <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl">
            AI-Powered Learning Platform
        </h1>
        <p class="mx-auto mt-6 max-w-2xl text-lg text-primary-100">
            Generate personalized lessons, take adaptive quizzes, prepare with full-length practice exams, and refine your writing — all powered by AI with full transparency.
        </p>
        <div class="mt-10 flex items-center justify-center gap-4">
            <a href="/signup" class="rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-primary-700 shadow-lg hover:bg-primary-50 transition">
                Get Started Free
            </a>
            <a href="/login" class="rounded-xl border-2 border-white/30 px-8 py-3.5 text-base font-semibold text-white hover:bg-white/10 transition">
                Log In
            </a>
        </div>
    </div>
</section>

<!-- Features -->
<section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
    <div class="text-center mb-16">
        <h2 class="text-3xl font-bold text-neutral-900">Everything you need to learn smarter</h2>
        <p class="mt-4 text-lg text-neutral-600">Four powerful tools, one intelligent platform.</p>
    </div>

    <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Lessons -->
        <div class="group rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm hover:shadow-md hover:border-primary-300 transition">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-primary-100 text-primary-600 group-hover:bg-primary-600 group-hover:text-white transition">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-neutral-900">Lessons</h3>
            <p class="mt-2 text-sm text-neutral-600">Generate AI-powered lessons on any topic, tailored to your difficulty level with rich math and science rendering.</p>
        </div>

        <!-- Quizzes -->
        <div class="group rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm hover:shadow-md hover:border-primary-300 transition">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15a2.25 2.25 0 0 1 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-neutral-900">Quizzes</h3>
            <p class="mt-2 text-sm text-neutral-600">Practice with adaptive quizzes featuring multiple question types, instant grading, and detailed AI explanations.</p>
        </div>

        <!-- Exams -->
        <div class="group rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm hover:shadow-md hover:border-primary-300 transition">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-neutral-900">Full Exams</h3>
            <p class="mt-2 text-sm text-neutral-600">Simulate real SAT, ACT, PreACT, and MCA exams with timed sections, scaled scoring, and domain breakdowns.</p>
        </div>

        <!-- Writing Studio -->
        <div class="group rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm hover:shadow-md hover:border-primary-300 transition">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-rose-100 text-rose-600 group-hover:bg-rose-600 group-hover:text-white transition">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-neutral-900">Writing Studio</h3>
            <p class="mt-2 text-sm text-neutral-600">Write, scan, and grade documents with AI-powered feedback, integrity tracking, and shareable reports.</p>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="bg-neutral-900">
    <div class="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8 text-center">
        <h2 class="text-2xl font-bold text-white sm:text-3xl">Ready to start learning?</h2>
        <p class="mt-4 text-neutral-400">Join SCHOOL AI and unlock your full potential with transparent, AI-powered education tools.</p>
        <a href="/signup" class="mt-8 inline-block rounded-xl bg-primary-600 px-8 py-3.5 text-base font-semibold text-white shadow-lg hover:bg-primary-500 transition">
            Create Free Account
        </a>
    </div>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layout.php';
?>
