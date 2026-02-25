/**
 * topics.js – Topics Page Logic
 *
 * Loads topics from API, renders cards with search and subject filter tabs,
 * and handles topic selection for lesson/quiz generation.
 *
 * @namespace SchoolAI.Topics
 */
(function () {
    'use strict';

    window.SchoolAI = window.SchoolAI || {};
    var Topics = SchoolAI.Topics = {};

    var _topics = [];
    var _activeSubject = 'all';

    // ── Init ────────────────────────────────────────────────────────────
    Topics.init = function () {
        if (SchoolAI.currentPage() !== 'topics') return;
        Topics.load();
    };

    // ── Load topics from API ────────────────────────────────────────────
    Topics.load = function () {
        var grid = document.getElementById('topics-grid');
        if (!grid) return;

        SchoolAI.API.get('/api/topics')
            .then(function (data) {
                _topics = Array.isArray(data) ? data : (data.topics || []);
                _buildSubjectTabs();
                Topics.render();
            })
            .catch(function () {
                var loading = document.getElementById('topics-loading');
                if (loading) loading.innerHTML = '<p class="text-sm text-red-600">Failed to load topics.</p>';
            });
    };

    // ── Build subject filter tabs ───────────────────────────────────────
    function _buildSubjectTabs() {
        var filtersEl = document.getElementById('subject-filters');
        if (!filtersEl) return;

        var subjects = [];
        _topics.forEach(function (t) {
            if (t.subject && subjects.indexOf(t.subject) === -1) subjects.push(t.subject);
        });

        subjects.forEach(function (s) {
            var btn = document.createElement('button');
            btn.className = 'subject-tab rounded-full px-4 py-1.5 text-sm font-medium bg-white text-neutral-600 border border-neutral-200 hover:border-primary-300 transition';
            btn.dataset.subject = s;
            btn.setAttribute('role', 'tab');
            btn.setAttribute('aria-selected', 'false');
            btn.textContent = s;
            filtersEl.appendChild(btn);
        });

        filtersEl.addEventListener('click', function (e) {
            var btn = e.target.closest('.subject-tab');
            if (!btn) return;
            filtersEl.querySelectorAll('.subject-tab').forEach(function (b) {
                b.classList.remove('active', 'bg-primary-100', 'text-primary-700', 'border-primary-200');
                b.classList.add('bg-white', 'text-neutral-600', 'border-neutral-200');
                b.setAttribute('aria-selected', 'false');
            });
            btn.classList.add('active', 'bg-primary-100', 'text-primary-700', 'border-primary-200');
            btn.classList.remove('bg-white', 'text-neutral-600', 'border-neutral-200');
            btn.setAttribute('aria-selected', 'true');
            _activeSubject = btn.dataset.subject;
            Topics.render();
        });
    }

    // ── Search ──────────────────────────────────────────────────────────
    Topics.setupSearch = function () {
        var search = document.getElementById('topic-search');
        if (!search) return;
        search.addEventListener('input', function () { Topics.render(); });
    };

    // ── Render topic cards ──────────────────────────────────────────────
    Topics.render = function () {
        var grid = document.getElementById('topics-grid');
        var loading = document.getElementById('topics-loading');
        var empty = document.getElementById('topics-empty');
        if (!grid) return;

        var q = '';
        var searchEl = document.getElementById('topic-search');
        if (searchEl) q = searchEl.value.toLowerCase();

        var filtered = _topics.filter(function (t) {
            var matchSubject = _activeSubject === 'all' || t.subject === _activeSubject;
            var matchSearch = !q ||
                (t.name && t.name.toLowerCase().indexOf(q) !== -1) ||
                (t.subject && t.subject.toLowerCase().indexOf(q) !== -1) ||
                (t.tags || []).some(function (tag) { return tag.toLowerCase().indexOf(q) !== -1; });
            return matchSubject && matchSearch;
        });

        if (loading) loading.classList.add('hidden');

        if (!filtered.length) {
            grid.innerHTML = '';
            if (empty) empty.classList.remove('hidden');
            return;
        }
        if (empty) empty.classList.add('hidden');

        grid.innerHTML = filtered.map(function (t) {
            var tags = (t.tags || []).map(function (tag) {
                return '<span class="inline-flex rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs text-neutral-600">' + _esc(tag) + '</span>';
            }).join('');
            var mappings = '';
            if (t.testMappings && t.testMappings.length) {
                mappings = '<div class="mt-3 flex flex-wrap gap-1.5">' + t.testMappings.map(function (m) {
                    var label = typeof m === 'string' ? m : (m.test || '');
                    return '<span class="inline-flex rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700">' + _esc(label) + '</span>';
                }).join('') + '</div>';
            }
            return '<div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-neutral-200 hover:ring-primary-300 hover:shadow-md transition cursor-pointer" tabindex="0" role="button" aria-label="View topic: ' + _esc(t.name) + '">' +
                '<h3 class="font-semibold text-neutral-900">' + _esc(t.name) + '</h3>' +
                '<p class="mt-1 text-xs text-primary-600 font-medium">' + _esc(t.subject || '') + '</p>' +
                '<div class="mt-3 flex flex-wrap gap-1.5">' + tags + '</div>' +
                mappings + '</div>';
        }).join('');

        // Bind click handlers for topic selection
        grid.querySelectorAll('[role="button"]').forEach(function (card, i) {
            card.addEventListener('click', function () {
                Topics.select(filtered[i]);
            });
            card.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
            });
        });
    };

    // ── Select a topic ──────────────────────────────────────────────────
    Topics.select = function (topic) {
        window.location.href = '/lessons/generate?topic=' + encodeURIComponent(topic.id || topic.name);
    };

    /** Get currently loaded topics. */
    Topics.getAll = function () { return _topics.slice(); };

    // ── Helpers ──────────────────────────────────────────────────────────
    function _esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }
})();
