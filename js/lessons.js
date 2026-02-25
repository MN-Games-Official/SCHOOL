/**
 * lessons.js – Lesson Generation and Library
 *
 * Manages the lessons library listing and the lesson generation workflow
 * including form setup, AI modal integration, and Markdown rendering.
 *
 * @namespace SchoolAI.Lessons
 */
(function () {
    'use strict';

    window.SchoolAI = window.SchoolAI || {};
    var Lessons = SchoolAI.Lessons = {};

    // ── Init ────────────────────────────────────────────────────────────
    Lessons.init = function () {
        if (SchoolAI.currentPage() !== 'lessons') return;

        // Determine which sub-page we're on
        var generateForm = document.getElementById('lesson-form');
        if (generateForm) {
            Lessons.setupGenerateForm();
        } else {
            Lessons.loadLessons();
        }
    };

    // ── Load Lessons List ───────────────────────────────────────────────
    /**
     * Fetch the user's lesson library and render it.
     */
    Lessons.loadLessons = function () {
        var list = document.getElementById('lessons-list');
        if (!list) return;

        SchoolAI.API.get('/api/lessons/list')
            .then(function (data) {
                var lessons = Array.isArray(data) ? data : (data.lessons || []);
                Lessons.renderLessonsList(lessons);
            })
            .catch(function () {
                var loading = document.getElementById('lessons-loading');
                if (loading) loading.innerHTML = '<p class="text-sm text-red-600">Failed to load lessons.</p>';
            });
    };

    // ── Render Library ──────────────────────────────────────────────────
    /**
     * Render the lessons library list.
     * @param {Array} lessons
     */
    Lessons.renderLessonsList = function (lessons) {
        var list = document.getElementById('lessons-list');
        var loading = document.getElementById('lessons-loading');
        var empty = document.getElementById('lessons-empty');
        if (!list) return;

        if (loading) loading.classList.add('hidden');

        if (!lessons || !lessons.length) {
            if (empty) empty.classList.remove('hidden');
            return;
        }

        list.innerHTML = lessons.map(function (l) {
            var topicLabel = '';
            if (l.topic) {
                topicLabel = typeof l.topic === 'string' ? l.topic : (l.topic.name || '');
            }
            return '<a href="/lessons/generate?view=' + encodeURIComponent(l.id) + '" class="block rounded-2xl bg-white p-5 shadow-sm ring-1 ring-neutral-200 hover:ring-primary-300 hover:shadow-md transition">' +
                '<div class="flex items-center justify-between">' +
                '<h3 class="font-semibold text-neutral-900">' + _esc(l.title || 'Untitled Lesson') + '</h3>' +
                '<span class="text-xs text-neutral-500">' + (l.createdAt ? new Date(l.createdAt).toLocaleDateString() : '') + '</span>' +
                '</div>' +
                '<div class="mt-2 flex flex-wrap items-center gap-2">' +
                (topicLabel ? '<span class="inline-flex rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700">' + _esc(topicLabel) + '</span>' : '') +
                (l.difficulty ? '<span class="inline-flex rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs text-neutral-600">Level ' + _esc(String(l.difficulty)) + '</span>' : '') +
                '</div></a>';
        }).join('');
    };

    // ── Setup Generate Form ─────────────────────────────────────────────
    /**
     * Wire up the lesson generation form, topic selector, and AI modal flow.
     */
    Lessons.setupGenerateForm = function () {
        var allTopics = [];
        var selectedTopics = [];
        var searchInput = document.getElementById('topic-search-input');
        var dropdown = document.getElementById('topic-dropdown');
        var selectedEl = document.getElementById('selected-topics');
        if (!searchInput) return;

        // Load topics for the selector
        SchoolAI.API.get('/api/topics')
            .then(function (data) {
                allTopics = Array.isArray(data) ? data : (data.topics || []);

                // Pre-select from URL
                var params = new URLSearchParams(window.location.search);
                var preselect = params.get('topic');
                if (preselect) {
                    var match = allTopics.find(function (t) { return (t.id || t.name) === preselect; });
                    if (match) { selectedTopics.push(match); _renderSelected(); }
                }
            })
            .catch(function () { /* topics load failure is non-critical */ });

        // Dropdown interactions
        searchInput.addEventListener('focus', showDropdown);
        searchInput.addEventListener('input', showDropdown);
        document.addEventListener('click', function (e) {
            if (!e.target.closest('#topic-search-input, #topic-dropdown')) {
                if (dropdown) dropdown.classList.add('hidden');
            }
        });

        function showDropdown() {
            if (!dropdown) return;
            var q = searchInput.value.toLowerCase();
            var filtered = allTopics.filter(function (t) {
                return selectedTopics.indexOf(t) === -1 &&
                    (!q || (t.name && t.name.toLowerCase().indexOf(q) !== -1) || (t.subject && t.subject.toLowerCase().indexOf(q) !== -1));
            });
            if (!filtered.length) { dropdown.classList.add('hidden'); return; }
            dropdown.innerHTML = filtered.slice(0, 20).map(function (t, i) {
                return '<button type="button" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary-50 transition" data-idx="' + i + '">' + _esc(t.name) + ' <span class="text-neutral-400 text-xs">' + _esc(t.subject || '') + '</span></button>';
            }).join('');
            dropdown.classList.remove('hidden');
            dropdown.querySelectorAll('button').forEach(function (btn, i) {
                btn.addEventListener('click', function () {
                    selectedTopics.push(filtered[i]);
                    searchInput.value = '';
                    dropdown.classList.add('hidden');
                    _renderSelected();
                });
            });
        }

        function _renderSelected() {
            if (!selectedEl) return;
            selectedEl.innerHTML = selectedTopics.map(function (t, i) {
                return '<span class="inline-flex items-center gap-1 rounded-full bg-primary-100 pl-3 pr-1.5 py-1 text-sm text-primary-700">' +
                    _esc(t.name) +
                    '<button type="button" class="ml-1 rounded-full p-0.5 hover:bg-primary-200 transition" data-remove="' + i + '" aria-label="Remove ' + _esc(t.name) + '">' +
                    '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>' +
                    '</button></span>';
            }).join('');
            selectedEl.querySelectorAll('[data-remove]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    selectedTopics.splice(parseInt(btn.dataset.remove), 1);
                    _renderSelected();
                });
            });
        }

        // Form submission
        var form = document.getElementById('lesson-form');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!selectedTopics.length) {
                    SchoolAI.toast('Please select at least one topic.', 'warning');
                    return;
                }
                Lessons.generateLesson(selectedTopics);
            });
        }
    };

    // ── Generate Lesson ─────────────────────────────────────────────────
    /**
     * Collect form data, show AI modal, and send generation request.
     * @param {Array} selectedTopics
     */
    Lessons.generateLesson = function (selectedTopics) {
        var payload = {
            topics: selectedTopics.map(function (t) { return t.id || t.name; }),
            difficulty: parseInt(document.getElementById('difficulty').value) || 5,
            length: document.getElementById('length').value || 'medium',
            model: document.getElementById('model').value || 'gpt-oss-120b',
            seed: document.getElementById('seed').value ? parseInt(document.getElementById('seed').value) : undefined
        };

        SchoolAI.showAiModal({
            title: 'Generate Lesson',
            operation: 'lesson_generate',
            model: payload.model,
            seed: payload.seed,
            summary: 'Generating a ' + payload.length + ' lesson on ' + payload.topics.join(', ') + ' at difficulty ' + payload.difficulty + '.',
            onConfirm: function () {
                SchoolAI.setAiModalLoading(true);
                _showLessonLoading();

                SchoolAI.API.post('/api/lessons/generate', payload)
                    .then(function (data) {
                        SchoolAI.setAiModalLoading(false);
                        SchoolAI.hideAiModal(false);
                        if (data.error) {
                            _showLessonError(data.error);
                            return;
                        }
                        Lessons.renderLesson(data);
                        Lessons.saveLessonToLibrary(data);
                    })
                    .catch(function () {
                        SchoolAI.setAiModalLoading(false);
                        SchoolAI.hideAiModal(false);
                        _showLessonError('Failed to generate lesson. Please try again.');
                    });
            }
        });
    };

    // ── Render Lesson ───────────────────────────────────────────────────
    /**
     * Render a generated lesson's Markdown content with KaTeX.
     * @param {object} lessonData
     */
    Lessons.renderLesson = function (lessonData) {
        var output = document.getElementById('lesson-output');
        var content = document.getElementById('lesson-content');
        var loading = document.getElementById('lesson-loading');

        if (output) output.classList.remove('hidden');
        if (loading) loading.classList.add('hidden');
        if (content) {
            content.classList.remove('hidden');
            var md = lessonData.content || lessonData.lesson || JSON.stringify(lessonData);
            SchoolAI.Markdown.renderElement(content, md);
        }
    };

    // ── Save to Library ─────────────────────────────────────────────────
    /**
     * Persist a generated lesson to the user's library.
     * @param {object} lesson
     */
    Lessons.saveLessonToLibrary = function (lesson) {
        if (!lesson || !lesson.id) return;
        // Save is typically handled server-side during generation.
        // This hook exists for any client-side caching if needed.
        SchoolAI.toast('Lesson saved to your library.', 'success');
    };

    // ── Internal helpers ────────────────────────────────────────────────
    function _showLessonLoading() {
        var output = document.getElementById('lesson-output');
        var content = document.getElementById('lesson-content');
        var loading = document.getElementById('lesson-loading');
        if (output) output.classList.remove('hidden');
        if (content) content.classList.add('hidden');
        if (loading) loading.classList.remove('hidden');
    }

    function _showLessonError(msg) {
        var content = document.getElementById('lesson-content');
        var loading = document.getElementById('lesson-loading');
        if (loading) loading.classList.add('hidden');
        if (content) {
            content.classList.remove('hidden');
            content.innerHTML = '<p class="text-red-600">' + _esc(msg) + '</p>';
        }
    }

    function _esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }
})();
