/**
 * integrity.js – Integrity Report Viewer
 *
 * Loads and renders integrity data for documents: event timeline,
 * AI interaction log, integrity flags, SHA-256 verification hashes,
 * and print-ready report export.
 *
 * @namespace SchoolAI.Integrity
 */
(function () {
    'use strict';

    window.SchoolAI = window.SchoolAI || {};
    var Integrity = SchoolAI.Integrity = {};

    // ── Init ────────────────────────────────────────────────────────────
    Integrity.init = function () {
        // Auto-init if on report page
        var docEl = document.querySelector('[data-doc-id]');
        if (!docEl || SchoolAI.currentPage() !== 'writing') return;
        // Report page has specific elements
        if (!document.getElementById('timeline')) return;

        var docId = docEl.dataset.docId;
        if (docId) Integrity.loadReport(docId);
    };

    // ── Load Report ─────────────────────────────────────────────────────
    /**
     * Fetch integrity data for a document and render all sections.
     * @param {string} docId
     * @returns {Promise<object>}
     */
    Integrity.loadReport = function (docId) {
        return SchoolAI.API.get('/api/writing/' + encodeURIComponent(docId) + '/integrity')
            .then(function (data) {
                // Metadata
                _setText('meta-title', data.title || 'Untitled');
                _setText('meta-author', data.author || '—');
                _setText('meta-created', data.createdAt ? new Date(data.createdAt).toLocaleString() : '—');
                _setText('meta-modified', data.updatedAt ? new Date(data.updatedAt).toLocaleString() : '—');
                _setText('meta-words', data.wordCount);

                // Hash
                var hashEl = document.getElementById('meta-hash');
                if (hashEl) {
                    if (data.hash) {
                        hashEl.textContent = data.hash;
                    } else {
                        Integrity.generateVerificationHash(data)
                            .then(function (hash) { hashEl.textContent = hash; })
                            .catch(function () { hashEl.textContent = '—'; });
                    }
                }

                // Render sections
                Integrity.renderTimeline(data.timeline || data.snapshots || []);
                Integrity.renderAiLog(data.aiInteractions || data.aiLog || []);
                Integrity.renderFlags(data.flags || []);

                // Export button
                var exportBtn = document.getElementById('export-btn');
                if (exportBtn) {
                    exportBtn.addEventListener('click', function () { Integrity.exportReport(); });
                }

                return data;
            })
            .catch(function () {
                var tl = document.getElementById('timeline');
                if (tl) tl.innerHTML = '<p class="text-sm text-red-600">Failed to load report.</p>';
            });
    };

    // ── Render Timeline ─────────────────────────────────────────────────
    /**
     * Render the event timeline.
     * @param {Array} events
     */
    Integrity.renderTimeline = function (events) {
        var container = document.getElementById('timeline');
        if (!container) return;

        if (!events.length) {
            container.innerHTML = '<p class="text-sm text-neutral-400">No timeline data available.</p>';
            return;
        }

        container.innerHTML = events.map(function (e) {
            var time = e.time || e.timestamp;
            return '<div class="flex items-center gap-3 text-sm">' +
                '<span class="w-36 text-neutral-500 shrink-0">' + (time ? new Date(time).toLocaleString() : '') + '</span>' +
                '<span class="h-2 w-2 rounded-full bg-primary-500 shrink-0"></span>' +
                '<span class="text-neutral-700">' + _esc(e.action || e.event || e.type || '') + '</span>' +
                '</div>';
        }).join('');
    };

    // ── Render AI Log ───────────────────────────────────────────────────
    /**
     * Render the AI interaction summary (collapsible entries).
     * @param {Array} interactions
     */
    Integrity.renderAiLog = function (interactions) {
        var container = document.getElementById('ai-log');
        if (!container) return;

        if (!interactions.length) {
            container.innerHTML = '<p class="text-sm text-neutral-400">No AI interactions recorded.</p>';
            return;
        }

        container.innerHTML = interactions.map(function (l, i) {
            var time = l.timestamp || l.time;
            return '<details class="rounded-lg bg-neutral-50 border border-neutral-200 text-xs">' +
                '<summary class="p-3 cursor-pointer font-medium text-neutral-700">' +
                _esc(l.action || l.type || 'Interaction ' + (i + 1)) +
                ' <span class="text-neutral-400">' + (time ? new Date(time).toLocaleString() : '') + '</span>' +
                '</summary>' +
                (l.summary ? '<div class="px-3 pb-3 text-neutral-600">' + _esc(l.summary) + '</div>' : '') +
                '</details>';
        }).join('');
    };

    // ── Render Flags ────────────────────────────────────────────────────
    /**
     * Render integrity flags (e.g., fast writing bursts).
     * @param {Array} flags
     */
    Integrity.renderFlags = function (flags) {
        var container = document.getElementById('integrity-flags');
        if (!container) return;

        if (!flags.length) {
            container.innerHTML =
                '<div class="flex items-center gap-2 rounded-lg bg-green-50 border border-green-200 p-3 text-sm text-green-700">' +
                '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>' +
                'No integrity flags. Document looks authentic.</div>';
            return;
        }

        container.innerHTML = flags.map(function (f) {
            var isHigh = f.severity === 'high';
            var cls = isHigh ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-amber-50 border border-amber-200 text-amber-700';
            return '<div class="flex items-center gap-2 rounded-lg p-3 text-sm ' + cls + '">' +
                '<svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>' +
                _esc(f.message || f.description || '') + '</div>';
        }).join('');
    };

    // ── Verification Hash ───────────────────────────────────────────────
    /**
     * Compute a SHA-256 hash for verification.
     * @param {object} data - Data to hash (uses JSON serialisation)
     * @returns {Promise<string>}
     */
    Integrity.generateVerificationHash = function (data) {
        var text = JSON.stringify(data);
        if (window.crypto && window.crypto.subtle) {
            var encoder = new TextEncoder();
            return window.crypto.subtle.digest('SHA-256', encoder.encode(text))
                .then(function (buffer) {
                    var bytes = new Uint8Array(buffer);
                    var hex = '';
                    bytes.forEach(function (b) { hex += b.toString(16).padStart(2, '0'); });
                    return hex;
                });
        }
        // Fallback: simple hash for older browsers
        return Promise.resolve(_simpleHash(text));
    };

    function _simpleHash(str) {
        var hash = 0;
        for (var i = 0; i < str.length; i++) {
            var c = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + c;
            hash |= 0;
        }
        return 'simple-' + Math.abs(hash).toString(16);
    }

    // ── Export Report ────────────────────────────────────────────────────
    /**
     * Generate a print-ready HTML report and open in a new window.
     */
    Integrity.exportReport = function () {
        var title = _getText('meta-title') || 'Integrity Report';
        var author = _getText('meta-author') || '';
        var created = _getText('meta-created') || '';
        var hash = _getText('meta-hash') || '';

        var timeline = document.getElementById('timeline');
        var aiLog = document.getElementById('ai-log');
        var flags = document.getElementById('integrity-flags');

        var html = '<!DOCTYPE html><html><head><meta charset="utf-8">' +
            '<title>' + _esc(title) + ' – Integrity Report</title>' +
            '<style>body{font-family:system-ui,sans-serif;max-width:800px;margin:2rem auto;padding:0 1rem;color:#1e293b;}' +
            'h1{font-size:1.5rem;margin-bottom:.5rem;}h2{font-size:1.1rem;margin-top:2rem;border-bottom:1px solid #e2e8f0;padding-bottom:.5rem;}' +
            '.meta{display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:1rem;font-size:.875rem;}' +
            '.meta dt{color:#64748b;}.meta dd{font-weight:500;}' +
            '.section{margin-top:1rem;}.item{padding:.5rem 0;border-bottom:1px solid #f1f5f9;font-size:.875rem;}' +
            '@media print{body{margin:0;padding:1rem;}}</style></head><body>' +
            '<h1>Integrity Report</h1>' +
            '<dl class="meta">' +
            '<dt>Document</dt><dd>' + _esc(title) + '</dd>' +
            '<dt>Author</dt><dd>' + _esc(author) + '</dd>' +
            '<dt>Created</dt><dd>' + _esc(created) + '</dd>' +
            '<dt>Hash</dt><dd style="font-family:monospace;font-size:.75rem;word-break:break-all;">' + _esc(hash) + '</dd>' +
            '</dl>' +
            '<h2>Timeline</h2><div class="section">' + (timeline ? timeline.innerHTML : '') + '</div>' +
            '<h2>AI Interactions</h2><div class="section">' + (aiLog ? aiLog.innerHTML : '') + '</div>' +
            '<h2>Integrity Flags</h2><div class="section">' + (flags ? flags.innerHTML : '') + '</div>' +
            '<p style="margin-top:2rem;font-size:.75rem;color:#94a3b8;">Generated ' + new Date().toLocaleString() + '</p>' +
            '</body></html>';

        var w = window.open('', '_blank');
        if (w) {
            w.document.write(html);
            w.document.close();
            w.print();
        }
    };

    // ── Internal helpers ────────────────────────────────────────────────
    function _setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value != null ? String(value) : '—';
    }

    function _getText(id) {
        var el = document.getElementById(id);
        return el ? el.textContent : '';
    }

    function _esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }
})();
