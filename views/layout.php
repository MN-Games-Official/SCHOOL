<?php
/**
 * Base HTML Layout
 *
 * Variables expected:
 *   $pageTitle   - string  Page title
 *   $pageContent - string  HTML content for the main area
 *   $pageScripts - string  Additional JS to include before </body>
 *   $currentUser - array|null  Current user or null for guest
 *   $currentPage - string  Current page identifier for nav highlighting
 *   $csrfToken   - string  CSRF token
 *   $guestLayout - bool    If true, render without sidebar (login/signup/landing)
 *
 * @package School
 */

$pageTitle   = $pageTitle   ?? 'SCHOOL AI';
$pageContent = $pageContent ?? '';
$pageScripts = $pageScripts ?? '';
$currentUser = $currentUser ?? null;
$currentPage = $currentPage ?? '';
$csrfToken   = $csrfToken   ?? (function_exists('generateCsrfToken') ? generateCsrfToken() : '');
$guestLayout = $guestLayout ?? false;

$navItems = [
    ['id' => 'dashboard',     'href' => '/dashboard',      'icon' => 'home',         'label' => 'Dashboard'],
    ['id' => 'topics',        'href' => '/topics',         'icon' => 'book-open',    'label' => 'Topics'],
    ['id' => 'lessons',       'href' => '/lessons',        'icon' => 'academic-cap', 'label' => 'Lessons'],
    ['id' => 'quizzes',       'href' => '/quizzes',        'icon' => 'clipboard',    'label' => 'Quizzes'],
    ['id' => 'exams',         'href' => '/exams',          'icon' => 'document-text','label' => 'Exams'],
    ['id' => 'writing',       'href' => '/writing',        'icon' => 'pencil-square','label' => 'Writing Studio'],
    ['id' => 'admin-topics',  'href' => '/admin/topics',   'icon' => 'cog',          'label' => 'Admin Topics'],
];
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <title><?= htmlspecialchars($pageTitle) ?> — SCHOOL AI</title>

    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary:   { 50:'#eef2ff',100:'#e0e7ff',200:'#c7d2fe',300:'#a5b4fc',400:'#818cf8',500:'#6366f1',600:'#4f46e5',700:'#4338ca',800:'#3730a3',900:'#312e81',950:'#1e1b4b' },
                    neutral:   { 50:'#f8fafc',100:'#f1f5f9',200:'#e2e8f0',300:'#cbd5e1',400:'#94a3b8',500:'#64748b',600:'#475569',700:'#334155',800:'#1e293b',900:'#0f172a',950:'#020617' },
                }
            }
        }
    }
    </script>

    <!-- KaTeX -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>

    <!-- Marked.js & DOMPurify -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        .high-contrast { --tw-bg-opacity: 1; filter: contrast(1.25); }
        .dyslexia-friendly { font-family: 'OpenDyslexic', 'Comic Sans MS', sans-serif !important; letter-spacing: 0.05em; line-height: 1.8; }
        .sidebar-link.active { background-color: rgba(99,102,241,0.15); color: #6366f1; }
    </style>
</head>
<body class="h-full bg-neutral-50 text-neutral-900 antialiased">

<?php if ($guestLayout): ?>
<!-- ═══════════════ Guest Layout (no sidebar) ═══════════════ -->
<div class="min-h-full">
    <nav class="bg-white border-b border-neutral-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <a href="/" class="flex items-center gap-2 text-xl font-bold text-primary-600" aria-label="SCHOOL AI Home">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342"/></svg>
                    SCHOOL AI
                </a>
                <div class="flex items-center gap-4">
                    <a href="/login" class="text-sm font-medium text-neutral-600 hover:text-primary-600 transition">Log In</a>
                    <a href="/signup" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-primary-700 transition">Sign Up</a>
                </div>
            </div>
        </div>
    </nav>
    <main>
        <?= $pageContent ?>
    </main>
</div>

<?php else: ?>
<!-- ═══════════════ Authenticated Layout ═══════════════ -->
<div class="flex h-full">

    <!-- Sidebar -->
    <aside id="sidebar" class="hidden lg:flex lg:flex-col w-64 bg-white border-r border-neutral-200 fixed inset-y-0 z-30" role="navigation" aria-label="Main navigation">
        <!-- Brand -->
        <div class="flex h-16 items-center gap-2 px-6 border-b border-neutral-200">
            <svg class="h-8 w-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342"/></svg>
            <span class="text-xl font-bold text-primary-600">SCHOOL AI</span>
        </div>

        <!-- Nav links -->
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1" aria-label="Sidebar navigation">
            <?php foreach ($navItems as $item): ?>
            <a href="<?= $item['href'] ?>"
               class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-neutral-700 hover:bg-neutral-100 hover:text-primary-600 transition <?= $currentPage === $item['id'] ? 'active' : '' ?>"
               aria-current="<?= $currentPage === $item['id'] ? 'page' : 'false' ?>">
                <?= _sidebarIcon($item['icon']) ?>
                <?= htmlspecialchars($item['label']) ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <!-- User section -->
        <?php if ($currentUser): ?>
        <div class="border-t border-neutral-200 p-4">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-100 text-primary-700 font-semibold text-sm" aria-hidden="true">
                    <?= strtoupper(substr($currentUser['username'] ?? '?', 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-neutral-900 truncate"><?= htmlspecialchars($currentUser['username'] ?? '') ?></p>
                    <p class="text-xs text-neutral-500 truncate"><?= htmlspecialchars($currentUser['email'] ?? '') ?></p>
                </div>
                <a href="/logout" class="text-neutral-400 hover:text-red-500 transition" aria-label="Log out" title="Log out">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </aside>

    <!-- Main area -->
    <div class="flex-1 lg:pl-64 flex flex-col min-h-full">

        <!-- Topbar -->
        <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-neutral-200 bg-white/95 backdrop-blur px-4 sm:px-6">
            <!-- Mobile menu button -->
            <button id="mobile-menu-btn" type="button" class="lg:hidden -ml-2 p-2 text-neutral-500 hover:text-neutral-700" aria-label="Open sidebar">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
            </button>

            <!-- Page title -->
            <h1 class="text-lg font-semibold text-neutral-900 truncate"><?= htmlspecialchars($pageTitle) ?></h1>

            <div class="flex-1"></div>

            <!-- Search trigger -->
            <button id="search-trigger" type="button" class="hidden sm:flex items-center gap-2 rounded-lg border border-neutral-300 bg-neutral-50 px-3 py-1.5 text-sm text-neutral-500 hover:border-primary-400 hover:text-primary-600 transition" aria-label="Search (Ctrl+K)">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <span>Search…</span>
                <kbd class="ml-2 rounded border border-neutral-300 bg-white px-1.5 py-0.5 text-xs text-neutral-400">⌘K</kbd>
            </button>

            <!-- Accessibility toggles -->
            <div class="flex items-center gap-1">
                <button id="toggle-contrast" type="button" class="p-2 text-neutral-400 hover:text-neutral-700 rounded-lg hover:bg-neutral-100 transition" aria-label="Toggle high contrast" title="High contrast">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/></svg>
                </button>
                <button id="toggle-dyslexia" type="button" class="p-2 text-neutral-400 hover:text-neutral-700 rounded-lg hover:bg-neutral-100 transition" aria-label="Toggle dyslexia-friendly font" title="Dyslexia-friendly">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>
                </button>
            </div>

            <!-- User dropdown -->
            <?php if ($currentUser): ?>
            <div class="relative" id="user-dropdown-container">
                <button id="user-dropdown-btn" type="button" class="flex items-center gap-2 rounded-lg p-1.5 hover:bg-neutral-100 transition" aria-haspopup="true" aria-expanded="false">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-primary-700 font-semibold text-sm">
                        <?= strtoupper(substr($currentUser['username'] ?? '?', 0, 1)) ?>
                    </div>
                    <span class="hidden sm:block text-sm font-medium text-neutral-700"><?= htmlspecialchars($currentUser['username'] ?? '') ?></span>
                    <svg class="h-4 w-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div id="user-dropdown-menu" class="hidden absolute right-0 mt-2 w-48 rounded-xl bg-white shadow-lg ring-1 ring-neutral-200 py-1 z-50" role="menu">
                    <a href="/dashboard" class="block px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50" role="menuitem">Dashboard</a>
                    <hr class="my-1 border-neutral-100">
                    <a href="/logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50" role="menuitem">Log Out</a>
                </div>
            </div>
            <?php endif; ?>
        </header>

        <!-- Main content -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8" id="main-content" role="main">
            <?= $pageContent ?>
        </main>
    </div>
</div>

<!-- Mobile sidebar overlay -->
<div id="mobile-overlay" class="hidden fixed inset-0 z-40 bg-neutral-900/50 lg:hidden" aria-hidden="true"></div>
<?php endif; ?>

<!-- ═══════════════ Global Components ═══════════════ -->

<!-- Toast Container -->
<div id="toast-container" class="fixed bottom-4 right-4 z-50 flex flex-col gap-2" aria-live="polite" aria-atomic="true"></div>

<!-- AI Request Modal -->
<div id="ai-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="ai-modal-title">
    <div class="fixed inset-0 bg-neutral-900/60 backdrop-blur-sm" id="ai-modal-backdrop"></div>
    <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-neutral-200 px-6 py-4">
            <h2 id="ai-modal-title" class="text-lg font-semibold text-neutral-900">AI Request Preview</h2>
            <button id="ai-modal-close" type="button" class="p-1 text-neutral-400 hover:text-neutral-600 transition" aria-label="Close modal">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-6 py-4 max-h-96 overflow-y-auto">
            <div id="ai-modal-preview" class="rounded-lg bg-neutral-50 border border-neutral-200 p-4 text-sm font-mono text-neutral-700 whitespace-pre-wrap">
                <!-- Request preview injected here -->
            </div>
        </div>
        <div class="flex items-center justify-end gap-3 border-t border-neutral-200 px-6 py-4">
            <button id="ai-modal-cancel" type="button" class="rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 transition">Cancel</button>
            <button id="ai-modal-send" type="button" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-primary-700 transition">
                <span class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                    Send Request
                </span>
            </button>
        </div>
    </div>
</div>

<script>
window.__CSRF__ = <?= json_encode($csrfToken) ?>;
window.__USER__ = <?= json_encode($currentUser) ?>;
window.__PAGE__ = <?= json_encode($currentPage) ?>;

// Mobile sidebar toggle
document.getElementById('mobile-menu-btn')?.addEventListener('click', () => {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-overlay');
    sidebar.classList.toggle('hidden');
    sidebar.classList.toggle('fixed');
    sidebar.classList.toggle('inset-y-0');
    sidebar.classList.toggle('left-0');
    overlay.classList.toggle('hidden');
});
document.getElementById('mobile-overlay')?.addEventListener('click', () => {
    document.getElementById('sidebar')?.classList.add('hidden');
    document.getElementById('mobile-overlay')?.classList.add('hidden');
});

// User dropdown
document.getElementById('user-dropdown-btn')?.addEventListener('click', () => {
    const menu = document.getElementById('user-dropdown-menu');
    menu.classList.toggle('hidden');
});
document.addEventListener('click', (e) => {
    if (!document.getElementById('user-dropdown-container')?.contains(e.target)) {
        document.getElementById('user-dropdown-menu')?.classList.add('hidden');
    }
});

// Accessibility toggles
document.getElementById('toggle-contrast')?.addEventListener('click', () => document.documentElement.classList.toggle('high-contrast'));
document.getElementById('toggle-dyslexia')?.addEventListener('click', () => document.body.classList.toggle('dyslexia-friendly'));

// AI Modal
document.getElementById('ai-modal-close')?.addEventListener('click', () => document.getElementById('ai-modal').classList.add('hidden'));
document.getElementById('ai-modal-cancel')?.addEventListener('click', () => document.getElementById('ai-modal').classList.add('hidden'));
document.getElementById('ai-modal-backdrop')?.addEventListener('click', () => document.getElementById('ai-modal').classList.add('hidden'));

// Toast helper
window.showToast = function(message, type = 'info') {
    const colors = { success: 'bg-green-600', error: 'bg-red-600', info: 'bg-primary-600', warning: 'bg-amber-500' };
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `${colors[type] || colors.info} text-white px-4 py-3 rounded-lg shadow-lg text-sm font-medium transform transition-all duration-300 translate-y-2 opacity-0`;
    toast.setAttribute('role', 'alert');
    toast.textContent = message;
    container.appendChild(toast);
    requestAnimationFrame(() => { toast.classList.remove('translate-y-2','opacity-0'); });
    setTimeout(() => { toast.classList.add('translate-y-2','opacity-0'); setTimeout(() => toast.remove(), 300); }, 4000);
};

// Render markdown+KaTeX helper
window.renderContent = function(markdown, targetEl) {
    if (typeof marked !== 'undefined' && typeof DOMPurify !== 'undefined') {
        targetEl.innerHTML = DOMPurify.sanitize(marked.parse(markdown));
        if (typeof renderMathInElement !== 'undefined') {
            renderMathInElement(targetEl, { delimiters: [
                { left: '$$', right: '$$', display: true },
                { left: '$', right: '$', display: false },
                { left: '\\(', right: '\\)', display: false },
                { left: '\\[', right: '\\]', display: true }
            ]});
        }
    }
};

// Ctrl+K search trigger
document.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        document.getElementById('search-trigger')?.click();
    }
});
</script>

<?= $pageScripts ?>
<script type="module" src="/js/app.js"></script>
</body>
</html>
<?php
/**
 * Render a simple SVG icon for the sidebar.
 */
function _sidebarIcon(string $name): string {
    $icons = [
        'home'          => '<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>',
        'book-open'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>',
        'academic-cap'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342"/>',
        'clipboard'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15a2.25 2.25 0 0 1 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>',
        'document-text' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>',
        'pencil-square'=> '<path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>',
        'cog'           => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>',
    ];
    $path = $icons[$name] ?? $icons['home'];
    return '<svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">' . $path . '</svg>';
}
?>
