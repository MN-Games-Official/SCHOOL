/**
 * analytics.js – Dashboard Analytics
 *
 * Loads and renders dashboard statistics, progress bars for topic performance,
 * and div-based bar charts for score trends.
 *
 * @namespace SchoolAI.Analytics
 */
(function () {
    'use strict';

    window.SchoolAI = window.SchoolAI || {};
    var Analytics = SchoolAI.Analytics = {};

    // ── Init ────────────────────────────────────────────────────────────
    Analytics.init = function () {
        if (SchoolAI.currentPage() !== 'dashboard') return;
        Analytics.loadStats();
    };

    // ── Load Stats ──────────────────────────────────────────────────────
    /**
     * Fetch dashboard statistics from the API.
     * @returns {Promise<object>}
     */
    Analytics.loadStats = function () {
        return SchoolAI.API.get('/api/dashboard/stats')
            .then(function (data) {
                Analytics.renderStats(data);
                if (data.progress) Analytics.renderProgressBars(data.progress);
                if (data.scoreTrends) Analytics.renderScoreTrends(data.scoreTrends);
                return data;
            })
            .catch(function () {
                // Stats are non-critical; fail silently
            });
    };

    // ── Render Stat Cards ───────────────────────────────────────────────
    /**
     * Populate the stat cards on the dashboard.
     * @param {object} stats
     */
    Analytics.renderStats = function (stats) {
        if (!stats) return;
        _setText('stat-lessons', stats.lessons);
        _setText('stat-quizzes', stats.quizzes);
        _setText('stat-exams', stats.exams);
        _setText('stat-docs', stats.documents);
    };

    // ── Render Progress Bars ────────────────────────────────────────────
    /**
     * Render topic/subject performance bars.
     * @param {object} data - { math: number, rw: number, science: number, ... }
     */
    Analytics.renderProgressBars = function (data) {
        if (!data) return;

        var mapping = {
            math: { bar: 'progress-math', pct: 'progress-math-pct' },
            rw: { bar: 'progress-rw', pct: 'progress-rw-pct' },
            science: { bar: 'progress-sci', pct: 'progress-sci-pct' }
        };

        Object.keys(mapping).forEach(function (key) {
            var val = data[key] != null ? data[key] : 0;
            var pct = Math.max(0, Math.min(100, val));
            var bar = document.getElementById(mapping[key].bar);
            var label = document.getElementById(mapping[key].pct);
            if (bar) bar.style.width = pct + '%';
            if (label) label.textContent = Math.round(pct) + '%';
        });
    };

    // ── Render Score Trends ─────────────────────────────────────────────
    /**
     * Render simple score trend charts using div-based bars (no external library).
     * @param {Array<{label:string, score:number}>} data
     */
    Analytics.renderScoreTrends = function (data) {
        if (!data || !data.length) return;

        // Find or create container
        var container = document.getElementById('score-trends');
        if (!container) return;

        var maxScore = 100;
        var barWidth = Math.max(20, Math.floor(100 / data.length));

        container.innerHTML =
            '<h3 class="text-lg font-semibold text-neutral-900 mb-4">Score Trends</h3>' +
            '<div class="flex items-end gap-2 h-40">' +
            data.map(function (item) {
                var pct = Math.max(0, Math.min(100, (item.score / maxScore) * 100));
                var color = pct >= 80 ? 'bg-green-500' : pct >= 60 ? 'bg-amber-500' : 'bg-red-500';
                return '<div class="flex flex-col items-center flex-1">' +
                    '<span class="text-xs font-medium text-neutral-700 mb-1">' + Math.round(item.score) + '</span>' +
                    '<div class="w-full rounded-t-lg transition-all duration-500 ' + color + '" style="height:' + pct + '%"></div>' +
                    '<span class="text-[10px] text-neutral-500 mt-1 truncate w-full text-center">' + _esc(item.label || '') + '</span>' +
                    '</div>';
            }).join('') +
            '</div>';
    };

    // ── Calculate Helpers ───────────────────────────────────────────────
    /**
     * Calculate minutes studied from activity data.
     * @param {Array<{duration:number}>} activities
     * @returns {number}
     */
    Analytics.calculateMinutesStudied = function (activities) {
        if (!activities) return 0;
        return activities.reduce(function (sum, a) { return sum + (a.duration || 0); }, 0);
    };

    /**
     * Calculate average score from an array of scores.
     * @param {number[]} scores
     * @returns {number}
     */
    Analytics.calculateAverageScore = function (scores) {
        if (!scores || !scores.length) return 0;
        var total = scores.reduce(function (sum, s) { return sum + s; }, 0);
        return Math.round(total / scores.length);
    };

    /**
     * Calculate per-topic performance.
     * @param {Array<{topic:string, score:number}>} data
     * @returns {object}
     */
    Analytics.calculateTopicPerformance = function (data) {
        if (!data) return {};
        var topics = {};
        data.forEach(function (item) {
            if (!topics[item.topic]) topics[item.topic] = { total: 0, count: 0 };
            topics[item.topic].total += item.score;
            topics[item.topic].count++;
        });
        var result = {};
        Object.keys(topics).forEach(function (t) {
            result[t] = Math.round(topics[t].total / topics[t].count);
        });
        return result;
    };

    // ── Internal ────────────────────────────────────────────────────────
    function _setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value != null ? value : 0;
    }

    function _esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }
})();
