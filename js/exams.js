/**
 * exams.js – Exam System
 *
 * Manages exam listing, configuration retrieval, multi-section exam building
 * via sequential AI calls (each showing a modal), and the exam builder UI.
 *
 * @namespace SchoolAI.Exams
 */
(function () {
    'use strict';

    window.SchoolAI = window.SchoolAI || {};
    var Exams = SchoolAI.Exams = {};

    // Exam type configurations
    var EXAM_CONFIGS = {
        sat: {
            name: 'SAT',
            sections: [
                { name: 'Reading & Writing Module 1', time: 32, questions: 27 },
                { name: 'Reading & Writing Module 2', time: 32, questions: 27 },
                { name: 'Math Module 1', time: 35, questions: 22 },
                { name: 'Math Module 2', time: 35, questions: 22 }
            ]
        },
        act: {
            name: 'ACT',
            sections: [
                { name: 'English', time: 45, questions: 75 },
                { name: 'Mathematics', time: 60, questions: 60 },
                { name: 'Reading', time: 35, questions: 40 },
                { name: 'Science', time: 35, questions: 40 }
            ]
        },
        preact: {
            name: 'PreACT',
            sections: [
                { name: 'English', time: 30, questions: 40 },
                { name: 'Mathematics', time: 40, questions: 36 },
                { name: 'Reading', time: 25, questions: 25 },
                { name: 'Science', time: 25, questions: 25 }
            ]
        },
        mca: {
            name: 'MCA',
            sections: [
                { name: 'Section 1', time: 60, questions: 30 },
                { name: 'Section 2', time: 60, questions: 30 }
            ]
        },
        topic: {
            name: 'Topic-Based',
            sections: [
                { name: 'Custom Section', time: 45, questions: 30 }
            ]
        }
    };

    // ── Init ────────────────────────────────────────────────────────────
    Exams.init = function () {
        if (SchoolAI.currentPage() !== 'exams') return;
    };

    // ── Load Exams ──────────────────────────────────────────────────────
    /**
     * Fetch the user's exam list.
     * @returns {Promise<Array>}
     */
    Exams.loadExams = function () {
        return SchoolAI.API.get('/api/exams/list')
            .then(function (data) {
                return Array.isArray(data) ? data : (data.exams || []);
            });
    };

    // ── Get Exam Config ─────────────────────────────────────────────────
    /**
     * Get exam configuration for a given type.
     * @param {string} type - sat, act, preact, mca, topic
     * @returns {object}
     */
    Exams.getExamConfig = function (type) {
        return EXAM_CONFIGS[type] || EXAM_CONFIGS.topic;
    };

    // ── Build Exam ──────────────────────────────────────────────────────
    /**
     * Generate exam sections via multiple AI calls (one per section).
     * Each section generation shows its own AI modal.
     *
     * @param {object} config
     * @param {string} config.type
     * @param {Array}  config.sections
     * @param {string} [config.grade]
     * @param {Array}  [config.topics]
     * @returns {Promise<object>} - The generated exam data
     */
    Exams.buildExam = function (config) {
        var sections = config.sections || [];
        var results = [];

        // Chain section generation sequentially
        var chain = Promise.resolve();
        sections.forEach(function (section, i) {
            chain = chain.then(function () {
                return _generateSection(config, section, i, sections.length);
            }).then(function (sectionData) {
                results.push(sectionData);
            });
        });

        return chain.then(function () {
            return { type: config.type, sections: results };
        });
    };

    /**
     * Generate a single exam section via AI.
     * @private
     */
    function _generateSection(config, section, index, total) {
        return new Promise(function (resolve, reject) {
            SchoolAI.showAiModal({
                title: 'Generating Section ' + (index + 1) + ' of ' + total,
                operation: 'exam_generate_section',
                summary: section.name + ' — ' + section.questions + ' questions, ' + section.time + ' min',
                onConfirm: function () {
                    SchoolAI.setAiModalLoading(true);
                    var payload = {
                        type: config.type,
                        sectionIndex: index,
                        sectionName: section.name,
                        questionCount: section.questions,
                        timeLimit: section.time,
                        grade: config.grade,
                        topics: config.topics
                    };

                    SchoolAI.API.post('/api/exams/generate-section', payload)
                        .then(function (data) {
                            SchoolAI.setAiModalLoading(false);
                            SchoolAI.hideAiModal(false);
                            if (data.error) {
                                SchoolAI.toast(data.error, 'error');
                                reject(new Error(data.error));
                                return;
                            }
                            SchoolAI.toast('Section ' + (index + 1) + ' generated.', 'success');
                            resolve(data);
                        })
                        .catch(function (err) {
                            SchoolAI.setAiModalLoading(false);
                            SchoolAI.hideAiModal(false);
                            reject(err);
                        });
                },
                onCancel: function () { reject(new Error('Cancelled')); }
            });
        });
    }

    // ── Render Exam Builder UI ──────────────────────────────────────────
    /**
     * Render the exam builder interface for a given type.
     * @param {string} type
     */
    Exams.renderExamBuilder = function (type) {
        var config = Exams.getExamConfig(type);
        var preview = document.getElementById('sections-preview');
        var callCount = document.getElementById('ai-call-count');

        if (callCount) callCount.textContent = String(config.sections.length);
        if (preview) {
            preview.innerHTML = config.sections.map(function (s) {
                return '<div class="flex items-center justify-between rounded-lg bg-neutral-50 border border-neutral-200 p-3">' +
                    '<span class="text-sm font-medium text-neutral-700">' + _esc(s.name) + '</span>' +
                    '<span class="text-xs text-neutral-500">' + s.questions + ' questions · ' + s.time + ' min</span>' +
                    '</div>';
            }).join('');
        }
    };

    // ── Store Exam Attempt Data ─────────────────────────────────────────
    var _attemptData = {};

    /** Store data for the current exam attempt. */
    Exams.storeAttempt = function (key, value) { _attemptData[key] = value; };

    /** Retrieve stored attempt data. */
    Exams.getAttempt = function (key) { return _attemptData[key]; };

    /** Clear attempt data. */
    Exams.clearAttempt = function () { _attemptData = {}; };

    // ── Helpers ──────────────────────────────────────────────────────────
    function _esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }
})();
