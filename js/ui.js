/**
 * ui.js – UI Component Library
 *
 * Provides reusable Tailwind-styled UI primitives that can be created
 * programmatically: cards, modals, tabs, steppers, badges, progress rings,
 * timers, dropdowns, search inputs, and confirm dialogs.
 *
 * @namespace SchoolAI.UI
 */
(function () {
    'use strict';

    window.SchoolAI = window.SchoolAI || {};
    var UI = SchoolAI.UI = {};

    // ── Helpers ─────────────────────────────────────────────────────────
    function _esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    function _el(tag, cls, html) {
        var el = document.createElement(tag);
        if (cls) el.className = cls;
        if (html) el.innerHTML = html;
        return el;
    }

    // ── Card ────────────────────────────────────────────────────────────
    /**
     * Create a styled card element.
     * @param {object} config
     * @param {string} [config.title]
     * @param {string} [config.body]       - HTML body content
     * @param {string} [config.footer]     - HTML footer content
     * @param {string} [config.className]  - Extra CSS classes
     * @returns {HTMLElement}
     */
    UI.createCard = function (config) {
        config = config || {};
        var card = _el('div', 'rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200 ' + (config.className || ''));
        if (config.title) {
            card.appendChild(_el('h3', 'font-semibold text-neutral-900 mb-3', _esc(config.title)));
        }
        if (config.body) {
            var body = _el('div', 'text-sm text-neutral-700');
            body.innerHTML = config.body;
            card.appendChild(body);
        }
        if (config.footer) {
            var footer = _el('div', 'mt-4 pt-4 border-t border-neutral-100 text-sm text-neutral-500');
            footer.innerHTML = config.footer;
            card.appendChild(footer);
        }
        return card;
    };

    // ── Modal ───────────────────────────────────────────────────────────
    /**
     * Create a modal element with backdrop overlay.
     * @param {object} config
     * @param {string} [config.title]
     * @param {string} [config.body]        - HTML body
     * @param {string} [config.confirmText] - Confirm button text
     * @param {string} [config.cancelText]  - Cancel button text
     * @param {Function} [config.onConfirm]
     * @param {Function} [config.onCancel]
     * @returns {HTMLElement}
     */
    UI.createModal = function (config) {
        config = config || {};
        var wrapper = _el('div', 'fixed inset-0 z-50 flex items-center justify-center p-4');
        wrapper.setAttribute('role', 'dialog');
        wrapper.setAttribute('aria-modal', 'true');

        var backdrop = _el('div', 'fixed inset-0 bg-neutral-900/60 backdrop-blur-sm');
        wrapper.appendChild(backdrop);

        var dialog = _el('div', 'relative w-full max-w-lg rounded-2xl bg-white shadow-2xl');

        // Header
        if (config.title) {
            var header = _el('div', 'flex items-center justify-between border-b border-neutral-200 px-6 py-4');
            header.appendChild(_el('h2', 'text-lg font-semibold text-neutral-900', _esc(config.title)));
            var closeBtn = _el('button', 'p-1 text-neutral-400 hover:text-neutral-600 transition', '&times;');
            closeBtn.setAttribute('aria-label', 'Close');
            closeBtn.addEventListener('click', function () { _closeModal(wrapper, config.onCancel); });
            header.appendChild(closeBtn);
            dialog.appendChild(header);
        }

        // Body
        if (config.body) {
            var body = _el('div', 'px-6 py-4 max-h-96 overflow-y-auto text-sm text-neutral-700');
            body.innerHTML = config.body;
            dialog.appendChild(body);
        }

        // Footer with buttons
        var footer = _el('div', 'flex items-center justify-end gap-3 border-t border-neutral-200 px-6 py-4');
        if (config.cancelText !== false) {
            var cancelBtn = _el('button', 'rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 transition', _esc(config.cancelText || 'Cancel'));
            cancelBtn.addEventListener('click', function () { _closeModal(wrapper, config.onCancel); });
            footer.appendChild(cancelBtn);
        }
        if (config.confirmText !== false) {
            var confirmBtn = _el('button', 'rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-primary-700 transition', _esc(config.confirmText || 'Confirm'));
            confirmBtn.addEventListener('click', function () {
                if (typeof config.onConfirm === 'function') config.onConfirm();
                _closeModal(wrapper);
            });
            footer.appendChild(confirmBtn);
        }
        dialog.appendChild(footer);

        wrapper.appendChild(dialog);
        backdrop.addEventListener('click', function () { _closeModal(wrapper, config.onCancel); });

        return wrapper;
    };

    function _closeModal(wrapper, cb) {
        wrapper.remove();
        if (typeof cb === 'function') cb();
    }

    // ── Tabs ────────────────────────────────────────────────────────────
    /**
     * Create a tab navigation component.
     * @param {object} config
     * @param {Array<{id:string, label:string}>} config.tabs
     * @param {string} [config.activeTab]  - ID of initially active tab
     * @param {Function} [config.onChange]  - Called with tab id on change
     * @returns {HTMLElement}
     */
    UI.createTabs = function (config) {
        config = config || {};
        var tabs = config.tabs || [];
        var activeId = config.activeTab || (tabs[0] && tabs[0].id);

        var container = _el('div', 'flex border-b border-neutral-200');
        container.setAttribute('role', 'tablist');

        tabs.forEach(function (tab) {
            var btn = _el('button', '', _esc(tab.label));
            btn.setAttribute('role', 'tab');
            btn.dataset.tabId = tab.id;
            _setTabActive(btn, tab.id === activeId);
            btn.addEventListener('click', function () {
                container.querySelectorAll('[role="tab"]').forEach(function (b) { _setTabActive(b, false); });
                _setTabActive(btn, true);
                if (typeof config.onChange === 'function') config.onChange(tab.id);
            });
            container.appendChild(btn);
        });

        return container;
    };

    function _setTabActive(btn, active) {
        btn.setAttribute('aria-selected', String(active));
        btn.className = active
            ? 'px-4 py-3 text-sm font-medium text-primary-600 border-b-2 border-primary-600'
            : 'px-4 py-3 text-sm font-medium text-neutral-500 border-b-2 border-transparent hover:text-neutral-700';
    }

    // ── Stepper ─────────────────────────────────────────────────────────
    /**
     * Create a step indicator.
     * @param {object} config
     * @param {string[]} config.steps  - Step labels
     * @param {number} [config.current=0] - Current step index
     * @returns {HTMLElement}
     */
    UI.createStepper = function (config) {
        config = config || {};
        var steps = config.steps || [];
        var current = config.current || 0;

        var container = _el('div', 'flex items-center gap-2');
        steps.forEach(function (label, i) {
            var done = i < current;
            var active = i === current;
            var circle = _el('div',
                'flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold shrink-0 ' +
                (done ? 'bg-primary-600 text-white' : active ? 'bg-primary-100 text-primary-700 ring-2 ring-primary-600' : 'bg-neutral-100 text-neutral-500'),
                done ? '✓' : String(i + 1)
            );
            container.appendChild(circle);

            var text = _el('span', 'text-xs font-medium ' + (active ? 'text-neutral-900' : 'text-neutral-500'), _esc(label));
            container.appendChild(text);

            if (i < steps.length - 1) {
                container.appendChild(_el('div', 'flex-1 h-px ' + (done ? 'bg-primary-400' : 'bg-neutral-200')));
            }
        });
        return container;
    };

    // ── Badge ───────────────────────────────────────────────────────────
    /**
     * Create a badge span.
     * @param {string} text
     * @param {string} [color='neutral'] - Tailwind color name
     * @returns {HTMLElement}
     */
    UI.createBadge = function (text, color) {
        color = color || 'neutral';
        return _el('span',
            'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium bg-' + color + '-100 text-' + color + '-700',
            _esc(text)
        );
    };

    // ── Progress Ring ───────────────────────────────────────────────────
    /**
     * Create an SVG progress ring.
     * @param {number} percent - 0-100
     * @returns {HTMLElement}
     */
    UI.createProgressRing = function (percent) {
        percent = Math.max(0, Math.min(100, percent || 0));
        var r = 36, c = 2 * Math.PI * r;
        var offset = c - (percent / 100) * c;
        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('width', '80');
        svg.setAttribute('height', '80');
        svg.setAttribute('viewBox', '0 0 80 80');
        svg.setAttribute('class', 'transform -rotate-90');
        svg.innerHTML =
            '<circle cx="40" cy="40" r="' + r + '" fill="none" stroke-width="6" class="stroke-neutral-200"/>' +
            '<circle cx="40" cy="40" r="' + r + '" fill="none" stroke-width="6" stroke-linecap="round" class="stroke-primary-600 transition-all duration-500" stroke-dasharray="' + c + '" stroke-dashoffset="' + offset + '"/>';

        var wrapper = _el('div', 'relative inline-flex items-center justify-center');
        wrapper.appendChild(svg);
        var label = _el('span', 'absolute text-sm font-bold text-neutral-900', Math.round(percent) + '%');
        wrapper.appendChild(label);
        return wrapper;
    };

    // ── Timer ───────────────────────────────────────────────────────────
    /**
     * Create a countdown timer element.
     * @param {number} seconds   - Starting seconds
     * @param {Function} [onTick]     - Called each second with remaining seconds
     * @param {Function} [onComplete] - Called when timer reaches 0
     * @returns {{el: HTMLElement, start: Function, pause: Function, resume: Function, stop: Function, remaining: Function}}
     */
    UI.createTimer = function (seconds, onTick, onComplete) {
        var remaining = seconds;
        var intervalId = null;

        var el = _el('div', 'flex items-center gap-2 rounded-lg bg-neutral-900 px-4 py-2 text-sm font-mono text-white');
        var display = _el('span', '', _formatTime(remaining));
        el.appendChild(display);

        function _formatTime(s) {
            var m = Math.floor(s / 60);
            var sec = s % 60;
            return String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
        }

        function tick() {
            remaining--;
            display.textContent = _formatTime(Math.max(0, remaining));
            if (typeof onTick === 'function') onTick(remaining);
            if (remaining <= 0) {
                clearInterval(intervalId);
                intervalId = null;
                if (typeof onComplete === 'function') onComplete();
            }
        }

        return {
            el: el,
            start: function () { if (!intervalId) intervalId = setInterval(tick, 1000); },
            pause: function () { clearInterval(intervalId); intervalId = null; },
            resume: function () { if (!intervalId && remaining > 0) intervalId = setInterval(tick, 1000); },
            stop: function () { clearInterval(intervalId); intervalId = null; remaining = 0; display.textContent = _formatTime(0); },
            remaining: function () { return remaining; }
        };
    };

    // ── Dropdown ─────────────────────────────────────────────────────────
    /**
     * Create a styled dropdown.
     * @param {Array<{value:string, label:string}>} options
     * @param {Function} [onChange] - Called with selected value
     * @returns {HTMLElement}
     */
    UI.createDropdown = function (options, onChange) {
        var select = _el('select', 'block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition');
        (options || []).forEach(function (opt) {
            var o = _el('option', '', _esc(opt.label || opt.value));
            o.value = opt.value;
            select.appendChild(o);
        });
        if (typeof onChange === 'function') {
            select.addEventListener('change', function () { onChange(select.value); });
        }
        return select;
    };

    // ── Search Input ────────────────────────────────────────────────────
    /**
     * Create a search input with debounce.
     * @param {string} [placeholder='Search…']
     * @param {Function} [onSearch] - Called with search query after debounce
     * @param {number} [delay=300] - Debounce delay in ms
     * @returns {HTMLElement}
     */
    UI.createSearchInput = function (placeholder, onSearch, delay) {
        delay = delay || 300;
        var wrapper = _el('div', 'relative');
        wrapper.innerHTML =
            '<svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>';
        var input = _el('input', 'block w-full rounded-lg border border-neutral-300 bg-white pl-10 pr-4 py-2.5 text-sm placeholder-neutral-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition');
        input.type = 'search';
        input.placeholder = placeholder || 'Search…';
        input.setAttribute('aria-label', placeholder || 'Search');
        wrapper.appendChild(input);

        var timer = null;
        if (typeof onSearch === 'function') {
            input.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () { onSearch(input.value); }, delay);
            });
        }

        return wrapper;
    };

    // ── Confirm Dialog ──────────────────────────────────────────────────
    /**
     * Show a confirmation dialog (returns Promise<boolean>).
     * @param {string} message
     * @returns {Promise<boolean>}
     */
    UI.showConfirm = function (message) {
        return new Promise(function (resolve) {
            var modal = UI.createModal({
                title: 'Confirm',
                body: '<p>' + _esc(message) + '</p>',
                confirmText: 'Confirm',
                cancelText: 'Cancel',
                onConfirm: function () { resolve(true); },
                onCancel: function () { resolve(false); }
            });
            document.body.appendChild(modal);
        });
    };
})();
