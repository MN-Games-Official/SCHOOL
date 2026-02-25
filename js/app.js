/**
 * app.js – Main Application Entry Point
 *
 * Initializes all modules, sets up global event listeners, toast system,
 * AI modal controller, CSRF helpers, and fetch wrapper.
 *
 * @namespace SchoolAI
 */
(function () {
    'use strict';

    window.SchoolAI = window.SchoolAI || {};

    // ── CSRF ────────────────────────────────────────────────────────────
    /** Get CSRF token from meta tag or global variable. */
    SchoolAI.getCsrfToken = function () {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return (meta && meta.getAttribute('content')) || window.__CSRF__ || '';
    };

    // ── Fetch Wrapper ───────────────────────────────────────────────────
    /**
     * Fetch wrapper that injects CSRF token on POST requests.
     * @param {string} url
     * @param {object} [opts]
     * @returns {Promise<Response>}
     */
    SchoolAI.fetch = function (url, opts) {
        opts = opts || {};
        opts.headers = opts.headers || {};
        if (!opts.headers['X-CSRF-Token']) {
            opts.headers['X-CSRF-Token'] = SchoolAI.getCsrfToken();
        }
        if (opts.method && opts.method.toUpperCase() === 'POST' && opts.body && !opts.headers['Content-Type']) {
            opts.headers['Content-Type'] = 'application/json';
        }
        return fetch(url, opts);
    };

    // ── Toast Notification System ───────────────────────────────────────
    var TOAST_COLORS = {
        success: 'bg-green-600',
        error: 'bg-red-600',
        info: 'bg-primary-600',
        warning: 'bg-amber-500'
    };

    /**
     * Show a toast notification.
     * @param {string} message
     * @param {'success'|'error'|'info'|'warning'} [type='info']
     */
    SchoolAI.toast = function (message, type) {
        type = type || 'info';
        var container = document.getElementById('toast-container');
        if (!container) return;

        var toast = document.createElement('div');
        toast.className = (TOAST_COLORS[type] || TOAST_COLORS.info) +
            ' text-white px-4 py-3 rounded-lg shadow-lg text-sm font-medium transform transition-all duration-300 translate-y-2 opacity-0';
        toast.setAttribute('role', 'alert');
        toast.textContent = message;
        container.appendChild(toast);

        requestAnimationFrame(function () {
            toast.classList.remove('translate-y-2', 'opacity-0');
        });

        setTimeout(function () {
            toast.classList.add('translate-y-2', 'opacity-0');
            setTimeout(function () { toast.remove(); }, 300);
        }, 4000);
    };

    // Also register the legacy global
    window.showToast = SchoolAI.toast;

    // ── AI Request Modal Controller ─────────────────────────────────────
    var _aiModalState = { onConfirm: null, onCancel: null };

    /**
     * Show the AI request preview modal.
     * @param {object} config
     * @param {string} config.title       - Modal title
     * @param {string} config.operation   - Operation name
     * @param {string} config.summary     - Description of what will happen
     * @param {string} [config.model]     - AI model name
     * @param {number} [config.seed]      - Seed value
     * @param {Function} config.onConfirm - Called when user clicks "Send Request"
     * @param {Function} [config.onCancel]- Called when user cancels
     */
    SchoolAI.showAiModal = function (config) {
        config = config || {};
        var modal = document.getElementById('ai-modal');
        var title = document.getElementById('ai-modal-title');
        var preview = document.getElementById('ai-modal-preview');
        var sendBtn = document.getElementById('ai-modal-send');
        if (!modal) return;

        // Populate
        if (title) title.textContent = config.title || 'AI Request Preview';
        var lines = [];
        if (config.operation) lines.push('Operation: ' + config.operation);
        if (config.model) lines.push('Model: ' + config.model);
        if (config.seed != null) lines.push('Seed: ' + config.seed);
        if (config.summary) lines.push('\n' + config.summary);
        if (preview) preview.textContent = lines.join('\n') || 'Ready to send request.';

        // Reset button state
        if (sendBtn) {
            sendBtn.disabled = false;
            sendBtn.innerHTML =
                '<span class="flex items-center gap-2">' +
                '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>' +
                'Send Request</span>';
        }

        // Store callbacks
        _aiModalState.onConfirm = config.onConfirm || null;
        _aiModalState.onCancel = config.onCancel || null;

        modal.classList.remove('hidden');
    };

    /** Hide the AI modal and optionally fire the cancel callback. */
    SchoolAI.hideAiModal = function (cancelled) {
        var modal = document.getElementById('ai-modal');
        if (modal) modal.classList.add('hidden');
        if (cancelled && typeof _aiModalState.onCancel === 'function') {
            _aiModalState.onCancel();
        }
        _aiModalState.onConfirm = null;
        _aiModalState.onCancel = null;
    };

    /** Set the AI modal into a loading state while a request is in progress. */
    SchoolAI.setAiModalLoading = function (loading) {
        var sendBtn = document.getElementById('ai-modal-send');
        if (!sendBtn) return;
        sendBtn.disabled = !!loading;
        if (loading) {
            sendBtn.innerHTML =
                '<span class="flex items-center gap-2">' +
                '<svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>' +
                'Processing…</span>';
        } else {
            sendBtn.innerHTML =
                '<span class="flex items-center gap-2">' +
                '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>' +
                'Send Request</span>';
        }
    };

    // ── Accessibility Toggle Handlers ────────────────────────────────────
    function _bindAccessibility() {
        var contrastBtn = document.getElementById('toggle-contrast');
        var dyslexiaBtn = document.getElementById('toggle-dyslexia');
        if (contrastBtn) {
            contrastBtn.addEventListener('click', function () {
                document.documentElement.classList.toggle('high-contrast');
            });
        }
        if (dyslexiaBtn) {
            dyslexiaBtn.addEventListener('click', function () {
                document.body.classList.toggle('dyslexia-friendly');
            });
        }
    }

    // ── Navigation Helpers ──────────────────────────────────────────────
    SchoolAI.navigate = function (url) { window.location.href = url; };
    SchoolAI.currentPage = function () { return window.__PAGE__ || ''; };
    SchoolAI.currentUser = function () { return window.__USER__ || null; };

    // ── Command Palette (Ctrl+K) ────────────────────────────────────────
    function _bindCommandPalette() {
        document.addEventListener('keydown', function (e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                if (SchoolAI.CommandPalette && SchoolAI.CommandPalette.open) {
                    SchoolAI.CommandPalette.open();
                } else {
                    var trigger = document.getElementById('search-trigger');
                    if (trigger) trigger.click();
                }
            }
        });
    }

    // ── AI Modal Event Wiring ───────────────────────────────────────────
    function _bindAiModal() {
        var sendBtn = document.getElementById('ai-modal-send');
        var cancelBtn = document.getElementById('ai-modal-cancel');
        var closeBtn = document.getElementById('ai-modal-close');
        var backdrop = document.getElementById('ai-modal-backdrop');

        function onSend() {
            if (typeof _aiModalState.onConfirm === 'function') {
                _aiModalState.onConfirm();
            }
        }
        function onCancel() { SchoolAI.hideAiModal(true); }

        if (sendBtn) sendBtn.addEventListener('click', onSend);
        if (cancelBtn) cancelBtn.addEventListener('click', onCancel);
        if (closeBtn) closeBtn.addEventListener('click', onCancel);
        if (backdrop) backdrop.addEventListener('click', onCancel);
    }

    // ── Initialise ──────────────────────────────────────────────────────
    function _init() {
        _bindAccessibility();
        _bindCommandPalette();
        _bindAiModal();

        // Init sub-modules if present
        var modules = [
            'Markdown', 'UI', 'API', 'Topics', 'Lessons', 'Quizzes',
            'Exams', 'TestRunner', 'Writing', 'Analytics', 'Integrity',
            'CommandPalette'
        ];
        modules.forEach(function (name) {
            var mod = SchoolAI[name];
            if (mod && typeof mod.init === 'function') {
                try { mod.init(); } catch (err) {
                    console.error('[SchoolAI.' + name + '] init failed:', err);
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', _init);
    } else {
        _init();
    }
})();
