<?php
/**
 * Signup Page
 */
$guestLayout = true;
$pageTitle   = 'Sign Up';
$csrfToken   = $csrfToken ?? (function_exists('generateCsrfToken') ? generateCsrfToken() : '');

ob_start();
?>
<div class="flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-neutral-900">Create your account</h2>
            <p class="mt-2 text-sm text-neutral-600">Start learning with SCHOOL AI today</p>
        </div>

        <div class="rounded-2xl bg-white p-8 shadow-lg ring-1 ring-neutral-200">
            <form id="signup-form" method="POST" action="/api/auth/signup" class="space-y-5" novalidate>
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div>
                    <label for="username" class="block text-sm font-medium text-neutral-700 mb-1">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="username"
                           class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:bg-white transition"
                           placeholder="3-32 characters, letters/numbers/underscores" aria-required="true">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-neutral-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email"
                           class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:bg-white transition"
                           placeholder="you@example.com" aria-required="true">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-neutral-700 mb-1">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password"
                           class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:bg-white transition"
                           placeholder="Minimum 8 characters" aria-required="true">
                </div>

                <div>
                    <label for="password_confirm" class="block text-sm font-medium text-neutral-700 mb-1">Confirm Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password"
                           class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:bg-white transition"
                           placeholder="Re-enter your password" aria-required="true">
                </div>

                <div id="signup-error" class="hidden rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700" role="alert"></div>

                <button type="submit" class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition">
                    Create Account
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-sm text-neutral-600">
            Already have an account?
            <a href="/login" class="font-semibold text-primary-600 hover:text-primary-500 transition">Log in</a>
        </p>
    </div>
</div>

<script>
document.getElementById('signup-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const errorEl = document.getElementById('signup-error');
    errorEl.classList.add('hidden');

    if (form.password.value !== form.password_confirm.value) {
        errorEl.textContent = 'Passwords do not match.';
        errorEl.classList.remove('hidden');
        return;
    }

    const body = { username: form.username.value, email: form.email.value, password: form.password.value };
    try {
        const res = await fetch('/api/auth/signup', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': form._csrf_token.value },
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = '/dashboard';
        } else {
            errorEl.textContent = data.error || 'Signup failed.';
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
