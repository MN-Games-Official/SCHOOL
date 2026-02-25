/**
 * writing.js – Writing Studio
 *
 * Manages document creation, editing with a Markdown toolbar, live preview,
 * auto-save with debounce, periodic snapshots with diff tracking, AI scan/grade
 * integration, rubric management, and integrity panel.
 *
 * @namespace SchoolAI.Writing
 */
(function () {
    'use strict';

    window.SchoolAI = window.SchoolAI || {};
    var Writing = SchoolAI.Writing = {};

    // ── State ───────────────────────────────────────────────────────────
    var _docId = '';
    var _showPreview = false;
    var _saveTimer = null;
    var _snapshotTimer = null;
    var _snapshots = [];
    var _keystrokeCount = 0;
    var _lastSnapshotContent = '';
    var _aiInteractionCount = 0;
    var _startTime = Date.now();

    var AUTOSAVE_DELAY = 30000;   // 30 seconds of inactivity
    var SNAPSHOT_INTERVAL = 120000; // 2 minutes

    // ── Toolbar Actions ─────────────────────────────────────────────────
    var TOOLBAR_INSERTS = {
        heading:   { before: '## ',  after: '' },
        h1:        { before: '# ',   after: '' },
        h2:        { before: '## ',  after: '' },
        h3:        { before: '### ', after: '' },
        bold:      { before: '**',   after: '**' },
        italic:    { before: '_',    after: '_' },
        underline: { before: '<u>',  after: '</u>' },
        ul:        { before: '- ',   after: '' },
        ol:        { before: '1. ',  after: '' },
        quote:     { before: '> ',   after: '' },
        code:      { before: '```\n', after: '\n```' },
        equation:  { before: '$',    after: '$' },
        table:     { before: '| Header | Header |\n|--------|--------|\n| Cell   | Cell   |', after: '' },
        link:      { before: '[',    after: '](url)' }
    };

    // Built-in rubric templates
    var RUBRIC_TEMPLATES = {
        general: 'Evaluate: thesis clarity, organization, evidence, grammar, style.',
        argumentative: 'AP-style argumentative: claim, reasoning, evidence, counterarguments, language.',
        narrative: 'Narrative writing: plot, character, setting, dialogue, literary devices.',
        expository: 'Expository writing: thesis, supporting details, structure, transitions, conclusion.',
        custom: ''
    };

    // ── Init ────────────────────────────────────────────────────────────
    Writing.init = function () {
        if (SchoolAI.currentPage() !== 'writing') return;
        var app = document.getElementById('writing-app');
        if (!app) return;
        _docId = app.dataset.docId || '';
        if (_docId) Writing.setupEditor();
    };

    // ── Document Management ─────────────────────────────────────────────
    Writing.createDocument = function (title) {
        return SchoolAI.API.post('/api/writing/create', { title: title || 'Untitled' });
    };

    Writing.saveDocument = function () {
        var textarea = document.getElementById('editor-textarea');
        if (!textarea || !_docId) return Promise.resolve();

        var status = document.getElementById('save-status');
        if (status) status.textContent = 'Saving…';

        return SchoolAI.API.post('/api/writing/' + encodeURIComponent(_docId) + '/save', { content: textarea.value })
            .then(function () { if (status) status.textContent = 'Saved'; })
            .catch(function () { if (status) status.textContent = 'Save failed'; });
    };

    Writing.listDocuments = function () {
        return SchoolAI.API.get('/api/writing/list')
            .then(function (data) { return Array.isArray(data) ? data : (data.documents || []); });
    };

    // ── Editor Setup ────────────────────────────────────────────────────
    Writing.setupEditor = function () {
        var textarea = document.getElementById('editor-textarea');
        if (!textarea) return;

        _startTime = Date.now();

        // Load document content
        SchoolAI.API.get('/api/writing/' + encodeURIComponent(_docId))
            .then(function (data) {
                if (data.content != null) textarea.value = data.content;
                _lastSnapshotContent = textarea.value;
                _updateCounts();
                _updateOutline();
            })
            .catch(function () {});

        // Input handler
        textarea.addEventListener('input', function () {
            _keystrokeCount++;
            _updateCounts();
            _updateOutline();
            var status = document.getElementById('save-status');
            if (status) status.textContent = 'Unsaved';

            // Live preview
            if (_showPreview) {
                var previewContent = document.getElementById('preview-content');
                if (previewContent) SchoolAI.Markdown.renderElement(previewContent, textarea.value);
            }

            // Auto-save with debounce
            clearTimeout(_saveTimer);
            _saveTimer = setTimeout(function () { Writing.saveDocument(); }, AUTOSAVE_DELAY);
        });

        // Toolbar buttons
        _bindToolbar(textarea);

        // Preview toggle
        var toggleBtn = document.getElementById('toggle-preview');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                _showPreview = !_showPreview;
                var pane = document.getElementById('preview-pane');
                if (pane) pane.classList.toggle('hidden', !_showPreview);
                if (_showPreview) {
                    var previewContent = document.getElementById('preview-content');
                    if (previewContent) SchoolAI.Markdown.renderElement(previewContent, textarea.value);
                }
            });
        }

        // Panel tabs
        _bindPanelTabs();

        // AI buttons
        _bindScanButton(textarea);
        _bindGradeButton(textarea);

        // Start snapshot timer
        _startSnapshots(textarea);
    };

    // ── Toolbar ─────────────────────────────────────────────────────────
    function _bindToolbar(textarea) {
        document.querySelectorAll('.toolbar-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var action = btn.dataset.action;
                var ins = TOOLBAR_INSERTS[action];
                if (!ins) return;

                var start = textarea.selectionStart;
                var end = textarea.selectionEnd;
                var selected = textarea.value.substring(start, end);

                textarea.value = textarea.value.substring(0, start) + ins.before + selected + ins.after + textarea.value.substring(end);
                textarea.focus();
                textarea.selectionStart = textarea.selectionEnd = start + ins.before.length + selected.length + ins.after.length;
                textarea.dispatchEvent(new Event('input'));
            });
        });
    }

    // ── Outline Generation ──────────────────────────────────────────────
    function _updateOutline() {
        var textarea = document.getElementById('editor-textarea');
        var outline = document.getElementById('doc-outline');
        if (!textarea || !outline) return;

        var headings = textarea.value.match(/^#{1,3}\s+.+$/gm) || [];
        if (!headings.length) {
            outline.innerHTML = '<p class="text-xs text-neutral-400 p-2">No headings found.</p>';
            return;
        }
        outline.innerHTML = headings.map(function (h) {
            var level = h.match(/^(#+)/)[1].length;
            var text = h.replace(/^#+\s*/, '');
            return '<button type="button" class="block w-full text-left px-2 py-1 text-xs rounded hover:bg-neutral-100 transition truncate" style="padding-left:' + (level * 8) + 'px" title="' + _esc(text) + '">' + _esc(text) + '</button>';
        }).join('');
    }

    // ── Counts ──────────────────────────────────────────────────────────
    function _updateCounts() {
        var textarea = document.getElementById('editor-textarea');
        if (!textarea) return;
        var text = textarea.value;
        var words = text.trim() ? text.trim().split(/\s+/).length : 0;
        var wordEl = document.getElementById('word-count');
        var charEl = document.getElementById('char-count');
        if (wordEl) wordEl.textContent = words + ' word' + (words !== 1 ? 's' : '');
        if (charEl) charEl.textContent = text.length + ' characters';
    }

    // ── Panel Tabs ──────────────────────────────────────────────────────
    function _bindPanelTabs() {
        var tabIssues = document.getElementById('tab-issues');
        var tabIntegrity = document.getElementById('tab-integrity');
        if (tabIssues) {
            tabIssues.addEventListener('click', function () {
                document.getElementById('panel-issues').classList.remove('hidden');
                document.getElementById('panel-integrity').classList.add('hidden');
                tabIssues.className = 'flex-1 px-4 py-3 text-sm font-medium text-primary-600 border-b-2 border-primary-600';
                tabIntegrity.className = 'flex-1 px-4 py-3 text-sm font-medium text-neutral-500 border-b-2 border-transparent hover:text-neutral-700';
            });
        }
        if (tabIntegrity) {
            tabIntegrity.addEventListener('click', function () {
                document.getElementById('panel-issues').classList.add('hidden');
                document.getElementById('panel-integrity').classList.remove('hidden');
                tabIntegrity.className = 'flex-1 px-4 py-3 text-sm font-medium text-primary-600 border-b-2 border-primary-600';
                tabIssues.className = 'flex-1 px-4 py-3 text-sm font-medium text-neutral-500 border-b-2 border-transparent hover:text-neutral-700';
                _renderIntegrityPanel();
            });
        }
    }

    // ── Snapshots ───────────────────────────────────────────────────────
    function _startSnapshots(textarea) {
        _snapshotTimer = setInterval(function () {
            var content = textarea.value;
            var diff = _computeDiff(_lastSnapshotContent, content);
            _snapshots.push({
                time: Date.now(),
                diff: diff,
                wordCount: content.trim() ? content.trim().split(/\s+/).length : 0,
                keystrokes: _keystrokeCount
            });
            _lastSnapshotContent = content;
            _keystrokeCount = 0;
        }, SNAPSHOT_INTERVAL);
    }

    function _computeDiff(oldText, newText) {
        if (oldText === newText) return { added: 0, removed: 0 };
        var oldWords = oldText.split(/\s+/);
        var newWords = newText.split(/\s+/);
        var added = Math.max(0, newWords.length - oldWords.length);
        var removed = Math.max(0, oldWords.length - newWords.length);
        return { added: added, removed: removed };
    }

    // ── AI Scan ─────────────────────────────────────────────────────────
    function _bindScanButton(textarea) {
        var btn = document.getElementById('scan-btn');
        if (!btn) return;

        btn.addEventListener('click', function () {
            var payload = {
                documentId: _docId,
                content: textarea.value,
                rubric: document.getElementById('rubric-select').value
            };

            SchoolAI.showAiModal({
                title: 'Scan Document',
                operation: 'writing_scan',
                summary: 'Analyzing your document for clarity, thesis, organization, evidence, and tone issues.',
                onConfirm: function () {
                    SchoolAI.setAiModalLoading(true);
                    _aiInteractionCount++;

                    SchoolAI.API.post('/api/writing/' + encodeURIComponent(_docId) + '/scan', payload)
                        .then(function (data) {
                            SchoolAI.setAiModalLoading(false);
                            SchoolAI.hideAiModal(false);
                            _renderIssues(data.issues || []);
                        })
                        .catch(function () {
                            SchoolAI.setAiModalLoading(false);
                            SchoolAI.hideAiModal(false);
                            SchoolAI.toast('Scan failed.', 'error');
                        });
                }
            });
        });
    }

    function _renderIssues(issues) {
        var panel = document.getElementById('panel-issues');
        if (!panel) return;

        if (!issues.length) {
            panel.innerHTML = '<p class="text-xs text-green-600">No issues found!</p>';
            return;
        }

        var colors = {
            clarity: 'border-blue-200 bg-blue-50 text-blue-700',
            thesis: 'border-purple-200 bg-purple-50 text-purple-700',
            organization: 'border-amber-200 bg-amber-50 text-amber-700',
            evidence: 'border-orange-200 bg-orange-50 text-orange-700',
            tone: 'border-teal-200 bg-teal-50 text-teal-700',
            error: 'border-red-200 bg-red-50 text-red-700'
        };

        panel.innerHTML = issues.map(function (iss) {
            var cls = colors[iss.type] || colors[iss.severity === 'error' ? 'error' : 'clarity'];
            return '<div class="rounded-lg border p-3 text-xs ' + cls + '">' +
                '<strong>' + _esc(iss.type || 'Issue') + '</strong>: ' + _esc(iss.description || iss.message || '') +
                (iss.location ? '<span class="block mt-1 text-[10px] opacity-75">Near: ' + _esc(iss.location) + '</span>' : '') +
                '</div>';
        }).join('');
    }

    // ── AI Grade ────────────────────────────────────────────────────────
    function _bindGradeButton(textarea) {
        var btn = document.getElementById('grade-btn');
        if (!btn) return;

        btn.addEventListener('click', function () {
            var rubricKey = document.getElementById('rubric-select').value;
            var rubric = RUBRIC_TEMPLATES[rubricKey] || rubricKey;
            var payload = {
                documentId: _docId,
                content: textarea.value,
                rubric: rubric
            };

            SchoolAI.showAiModal({
                title: 'Grade Document',
                operation: 'writing_grade',
                summary: 'Grading your document with rubric: ' + rubricKey,
                onConfirm: function () {
                    SchoolAI.setAiModalLoading(true);
                    _aiInteractionCount++;

                    SchoolAI.API.post('/api/writing/' + encodeURIComponent(_docId) + '/grade', payload)
                        .then(function (data) {
                            SchoolAI.setAiModalLoading(false);
                            SchoolAI.hideAiModal(false);
                            SchoolAI.toast('Grade: ' + (data.grade || data.score || 'Complete'), 'success');
                        })
                        .catch(function () {
                            SchoolAI.setAiModalLoading(false);
                            SchoolAI.hideAiModal(false);
                            SchoolAI.toast('Grading failed.', 'error');
                        });
                }
            });
        });
    }

    // ── Integrity Panel ─────────────────────────────────────────────────
    function _renderIntegrityPanel() {
        var panel = document.getElementById('panel-integrity');
        if (!panel) return;

        var elapsed = Math.round((Date.now() - _startTime) / 60000);
        var totalKeystrokes = 0;
        _snapshots.forEach(function (s) { totalKeystrokes += s.keystrokes || 0; });
        totalKeystrokes += _keystrokeCount;

        panel.innerHTML =
            '<div class="space-y-3">' +
            '<div class="rounded-lg bg-neutral-50 border border-neutral-200 p-3">' +
            '<p class="text-xs font-medium text-neutral-700">Revisions (snapshots)</p>' +
            '<p class="text-lg font-bold text-neutral-900">' + _snapshots.length + '</p></div>' +
            '<div class="rounded-lg bg-neutral-50 border border-neutral-200 p-3">' +
            '<p class="text-xs font-medium text-neutral-700">AI Interactions</p>' +
            '<p class="text-lg font-bold text-neutral-900">' + _aiInteractionCount + '</p></div>' +
            '<div class="rounded-lg bg-neutral-50 border border-neutral-200 p-3">' +
            '<p class="text-xs font-medium text-neutral-700">Writing Time</p>' +
            '<p class="text-lg font-bold text-neutral-900">~' + elapsed + ' min</p></div>' +
            '<div class="rounded-lg bg-neutral-50 border border-neutral-200 p-3">' +
            '<p class="text-xs font-medium text-neutral-700">Keystrokes (approx)</p>' +
            '<p class="text-lg font-bold text-neutral-900">' + totalKeystrokes + '</p></div>' +
            '</div>';
    }

    // ── Rubric Templates ────────────────────────────────────────────────
    Writing.getRubricTemplates = function () { return Object.assign({}, RUBRIC_TEMPLATES); };

    // ── Helpers ──────────────────────────────────────────────────────────
    function _esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }
})();
