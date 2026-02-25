/**
 * markdown.js – Markdown + LaTeX Renderer
 *
 * Uses marked.js for Markdown, KaTeX auto-render for math, and DOMPurify
 * for HTML sanitisation. Exposes SchoolAI.Markdown.render / renderInline /
 * renderElement helpers.
 *
 * @namespace SchoolAI.Markdown
 */
(function () {
    'use strict';

    window.SchoolAI = window.SchoolAI || {};
    var MD = SchoolAI.Markdown = {};

    // KaTeX delimiter config shared across all render calls
    var KATEX_DELIMITERS = [
        { left: '$$', right: '$$', display: true },
        { left: '$', right: '$', display: false },
        { left: '\\(', right: '\\)', display: false },
        { left: '\\[', right: '\\]', display: true }
    ];

    /** Configure marked.js options on first use. */
    MD.init = function () {
        if (typeof marked !== 'undefined') {
            marked.setOptions({
                breaks: true,
                gfm: true,
                headerIds: false,
                mangle: false
            });
        }
    };

    // ── Render full Markdown to HTML string ─────────────────────────────
    /**
     * Render Markdown text to a sanitised HTML string.
     * After Markdown parsing, KaTeX auto-render is applied to an off-screen
     * element so the returned HTML includes rendered math.
     *
     * @param {string} text - Raw Markdown/LaTeX text
     * @returns {string} Sanitised HTML
     */
    MD.render = function (text) {
        if (!text) return '';

        var html = '';
        if (typeof marked !== 'undefined') {
            html = marked.parse(text);
        } else {
            html = text;
        }

        // Sanitise
        if (typeof DOMPurify !== 'undefined') {
            html = DOMPurify.sanitize(html);
        }

        // Render KaTeX in a temporary container to get HTML with math
        var tmp = document.createElement('div');
        tmp.innerHTML = html;
        _renderKatex(tmp);
        return tmp.innerHTML;
    };

    // ── Render inline Markdown ──────────────────────────────────────────
    /**
     * Render inline Markdown (no block-level elements).
     * @param {string} text
     * @returns {string}
     */
    MD.renderInline = function (text) {
        if (!text) return '';

        var html = '';
        if (typeof marked !== 'undefined' && marked.parseInline) {
            html = marked.parseInline(text);
        } else {
            html = text;
        }

        if (typeof DOMPurify !== 'undefined') {
            html = DOMPurify.sanitize(html);
        }

        var tmp = document.createElement('span');
        tmp.innerHTML = html;
        _renderKatex(tmp);
        return tmp.innerHTML;
    };

    // ── Render into a DOM element ───────────────────────────────────────
    /**
     * Render Markdown content into a DOM element (sets innerHTML and runs KaTeX).
     * @param {HTMLElement} element
     * @param {string} [text] - If omitted, uses element.textContent
     */
    MD.renderElement = function (element, text) {
        if (!element) return;
        var source = text != null ? text : element.textContent;
        element.innerHTML = MD.render(source);
    };

    // ── Internal: run KaTeX auto-render on element ──────────────────────
    function _renderKatex(el) {
        if (typeof renderMathInElement === 'function') {
            try {
                renderMathInElement(el, {
                    delimiters: KATEX_DELIMITERS,
                    throwOnError: false
                });
            } catch (e) {
                // Silently swallow KaTeX errors for malformed math
            }
        }
    }

    // Also register the legacy global helper
    window.renderContent = function (markdown, targetEl) {
        if (targetEl) {
            MD.renderElement(targetEl, markdown);
        }
    };
})();
