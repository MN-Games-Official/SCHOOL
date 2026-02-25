<?php
/**
 * Login Page
 */
$guestLayout = true;
$pageTitle   = 'Log In';
$csrfToken   = $csrfToken ?? (function_exists('generateCsrfToken') ? generateCsrfToken() : '');

ob_start();
?>
<div class="flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-neutral-900">Welcome back</h2>
            <p class="mt-2 text-sm text-neutral-600">Sign in to your SCHOOL AI account</p>
        </div>

        <div class="rounded-2xl bg-white p-8 shadow-lg ring-1 ring-neutral-200">
            <form id="login-form" method="POST" action="/api/auth/login" class="space-y-5" novalidate>
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div>
                    <label for="username" class="block text-sm font-medium text-neutral-700 mb-1">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="username"
                           class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:bg-white transition"
                           placeholder="Enter your username" aria-required="true">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-neutral-700 mb-1">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                           class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:bg-white transition"
                           placeholder="Enter your password" aria-required="true">
                </div>

                <div id="login-error" class="hidden rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700" role="alert"></div>

                <button type="submit" class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition">
                    Log In
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-sm text-neutral-600">
            Don't have an account?
            <a href="/signup" class="font-semibold text-primary-600 hover:text-primary-500 transition">Sign up</a>
        </p>
    </div>
</div>

<script>
document.getElementById('login-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const errorEl = document.getElementById('login-error');
    errorEl.classList.add('hidden');

    const body = { username: form.username.value, password: form.password.value };
    try {
        const res = await fetch('/api/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': form._csrf_token.value },
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = '/dashboard';
        } else {
            errorEl.textContent = data.error || 'Login failed.';
            errorEl.classList.remove('hidden');
        }
    } catch {
        errorEl.textContent = 'Network error. Please try again.';
        errorEl.classList.remove('hidden');
    }
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
