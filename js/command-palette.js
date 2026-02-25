/**
 * command-palette.js – Command Palette (Ctrl+K)
 *
 * Shows a searchable modal overlay for quick navigation across pages,
 * topics, recent lessons, quizzes, and quick actions. Supports keyboard
 * navigation and fuzzy matching.
 *
 * @namespace SchoolAI.CommandPalette
 */
(function () {
    'use strict';

    window.SchoolAI = window.SchoolAI || {};
    var CP = SchoolAI.CommandPalette = {};

    var _overlay = null;
    var _input = null;
    var _results = null;
    var _items = [];
    var _selected = 0;
    var _visible = false;

    // ── Default commands ────────────────────────────────────────────────
    var DEFAULT_ITEMS = [
        { type: 'page', label: 'Dashboard', url: '/dashboard', keywords: 'home overview stats' },
        { type: 'page', label: 'Topics', url: '/topics', keywords: 'browse subjects' },
        { type: 'page', label: 'Lessons', url: '/lessons', keywords: 'library generated' },
        { type: 'page', label: 'Quizzes', url: '/quizzes', keywords: 'quiz test practice' },
        { type: 'page', label: 'Exams', url: '/exams', keywords: 'sat act preact mca exam' },
        { type: 'page', label: 'Writing Studio', url: '/writing', keywords: 'write essay document editor' },
        { type: 'action', label: 'Generate Lesson', url: '/lessons/generate', keywords: 'create new ai lesson' },
        { type: 'action', label: 'New Quiz', url: '/quizzes/generate', keywords: 'create generate quiz test' },
        { type: 'action', label: 'New Document', url: '/writing', keywords: 'create write new document' },
        { type: 'action', label: 'Start Exam', url: '/exams/builder', keywords: 'begin test sat act build' }
    ];

    // ── Init ────────────────────────────────────────────────────────────
    CP.init = function () {
        _buildOverlay();

        // Bind search trigger button
        var trigger = document.getElementById('search-trigger');
        if (trigger) {
            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                CP.open();
            });
        }
    };

    // ── Open / Close ────────────────────────────────────────────────────
    CP.open = function () {
        if (_visible) return;
        _visible = true;
        _items = DEFAULT_ITEMS.slice();
        _selected = 0;
        if (_overlay) _overlay.classList.remove('hidden');
        if (_input) { _input.value = ''; _input.focus(); }
        _renderResults('');
    };

    CP.close = function () {
        _visible = false;
        if (_overlay) _overlay.classList.add('hidden');
    };

    CP.toggle = function () {
        if (_visible) CP.close(); else CP.open();
    };

    // ── Build Overlay ───────────────────────────────────────────────────
    function _buildOverlay() {
        _overlay = document.createElement('div');
        _overlay.className = 'hidden fixed inset-0 z-50 flex items-start justify-center pt-[15vh] p-4';
        _overlay.setAttribute('role', 'dialog');
        _overlay.setAttribute('aria-modal', 'true');
        _overlay.setAttribute('aria-label', 'Command palette');

        // Backdrop
        var backdrop = document.createElement('div');
        backdrop.className = 'fixed inset-0 bg-neutral-900/50 backdrop-blur-sm';
        backdrop.addEventListener('click', CP.close);
        _overlay.appendChild(backdrop);

        // Panel
        var panel = document.createElement('div');
        panel.className = 'relative w-full max-w-lg rounded-2xl bg-white shadow-2xl ring-1 ring-neutral-200 overflow-hidden';

        // Search input
        var inputWrapper = document.createElement('div');
        inputWrapper.className = 'flex items-center gap-3 border-b border-neutral-200 px-4';
        inputWrapper.innerHTML =
            '<svg class="h-5 w-5 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>';
        _input = document.createElement('input');
        _input.type = 'text';
        _input.className = 'flex-1 border-none bg-transparent py-4 text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-0';
        _input.placeholder = 'Search pages, topics, actions…';
        _input.setAttribute('aria-label', 'Search commands');
        inputWrapper.appendChild(_input);

        var kbd = document.createElement('kbd');
        kbd.className = 'hidden sm:inline-block rounded border border-neutral-300 bg-neutral-50 px-1.5 py-0.5 text-xs text-neutral-400';
        kbd.textContent = 'ESC';
        inputWrapper.appendChild(kbd);
        panel.appendChild(inputWrapper);

        // Results
        _results = document.createElement('div');
        _results.className = 'max-h-72 overflow-y-auto py-2';
        _results.setAttribute('role', 'listbox');
        panel.appendChild(_results);

        _overlay.appendChild(panel);
        document.body.appendChild(_overlay);

        // Event listeners
        _input.addEventListener('input', function () {
            _selected = 0;
            _renderResults(_input.value);
        });

        _input.addEventListener('keydown', function (e) {
            var items = _results.querySelectorAll('[role="option"]');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                _selected = Math.min(_selected + 1, items.length - 1);
                _highlightSelected(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                _selected = Math.max(_selected - 1, 0);
                _highlightSelected(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (items[_selected]) items[_selected].click();
            } else if (e.key === 'Escape') {
                CP.close();
            }
        });
    }

    // ── Render Results ──────────────────────────────────────────────────
    function _renderResults(query) {
        if (!_results) return;
        var q = (query || '').toLowerCase().trim();
        var filtered = _items.filter(function (item) {
            if (!q) return true;
            return _fuzzyMatch(q, item.label) || _fuzzyMatch(q, item.keywords || '');
        });

        if (!filtered.length) {
            _results.innerHTML = '<div class="px-4 py-8 text-center text-sm text-neutral-400">No results found.</div>';
            return;
        }

        _results.innerHTML = '';
        filtered.forEach(function (item, i) {
            var el = document.createElement('button');
            el.className = 'flex w-full items-center gap-3 px-4 py-2.5 text-sm text-left transition ' +
                (i === _selected ? 'bg-primary-50 text-primary-700' : 'text-neutral-700 hover:bg-neutral-50');
            el.setAttribute('role', 'option');
            el.setAttribute('aria-selected', i === _selected ? 'true' : 'false');

            var icon = item.type === 'action' ? _actionIcon() : _pageIcon();
            var badge = item.type === 'action'
                ? '<span class="ml-auto rounded-full bg-primary-100 px-2 py-0.5 text-[10px] font-medium text-primary-700">Action</span>'
                : '';

            el.innerHTML = icon + '<span class="flex-1 truncate">' + _esc(item.label) + '</span>' + badge;

            el.addEventListener('click', function () {
                CP.close();
                if (item.url) window.location.href = item.url;
            });

            el.addEventListener('mouseenter', function () {
                _selected = i;
                _highlightSelected(_results.querySelectorAll('[role="option"]'));
            });

            _results.appendChild(el);
        });
    }

    function _highlightSelected(items) {
        items.forEach(function (el, i) {
            if (i === _selected) {
                el.classList.add('bg-primary-50', 'text-primary-700');
                el.classList.remove('text-neutral-700');
                el.setAttribute('aria-selected', 'true');
                el.scrollIntoView({ block: 'nearest' });
            } else {
                el.classList.remove('bg-primary-50', 'text-primary-700');
                el.classList.add('text-neutral-700');
                el.setAttribute('aria-selected', 'false');
            }
        });
    }

    // ── Fuzzy Match ─────────────────────────────────────────────────────
    function _fuzzyMatch(query, text) {
        text = text.toLowerCase();
        var qi = 0;
        for (var ti = 0; ti < text.length && qi < query.length; ti++) {
            if (text[ti] === query[qi]) qi++;
        }
        return qi === query.length;
    }

    // ── Icons ───────────────────────────────────────────────────────────
    function _pageIcon() {
        return '<svg class="h-4 w-4 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>';
    }

    function _actionIcon() {
        return '<svg class="h-4 w-4 text-primary-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z"/></svg>';
    }

    // ── Helpers ──────────────────────────────────────────────────────────
    function _esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }
})();
