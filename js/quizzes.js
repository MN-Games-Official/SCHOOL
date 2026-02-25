/**
 * quizzes.js – Quiz Generation and Taking
 *
 * Handles quiz library listing, generation via AI modal, quiz rendering
 * with multiple question types, answer collection, grading, review display,
 * and AI-powered explanations and hints.
 *
 * @namespace SchoolAI.Quizzes
 */
(function () {
    'use strict';

    window.SchoolAI = window.SchoolAI || {};
    var Quizzes = SchoolAI.Quizzes = {};

    // ── Init ────────────────────────────────────────────────────────────
    Quizzes.init = function () {
        if (SchoolAI.currentPage() !== 'quizzes') return;
        // Page-level scripts in the views handle specific page init
    };

    // ── Load Quiz List ──────────────────────────────────────────────────
    /**
     * Fetch the user's quiz history.
     * @returns {Promise<Array>}
     */
    Quizzes.loadQuizzes = function () {
        return SchoolAI.API.get('/api/quizzes/list')
            .then(function (data) {
                return Array.isArray(data) ? data : (data.quizzes || []);
            });
    };

    // ── Generate Quiz ───────────────────────────────────────────────────
    /**
     * Collect form data, show AI modal, and send quiz generation request.
     * @param {object} payload - Quiz configuration
     * @returns {Promise<object>}
     */
    Quizzes.generateQuiz = function (payload) {
        return new Promise(function (resolve, reject) {
            SchoolAI.showAiModal({
                title: 'Generate Quiz',
                operation: 'quiz_generate',
                model: payload.model || 'gpt-oss-120b',
                summary: payload.questionCount + ' questions on ' + (payload.topic || 'selected topic') +
                         ' (' + payload.difficultyDistribution + ' difficulty)',
                onConfirm: function () {
                    SchoolAI.setAiModalLoading(true);
                    SchoolAI.API.post('/api/quizzes/generate', payload)
                        .then(function (data) {
                            SchoolAI.setAiModalLoading(false);
                            SchoolAI.hideAiModal(false);
                            if (data.error) {
                                SchoolAI.toast(data.error, 'error');
                                reject(new Error(data.error));
                                return;
                            }
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
    };

    // ── Render Quiz ─────────────────────────────────────────────────────
    /**
     * Render a full quiz into the quiz-taking UI.
     * @param {object} quizData
     * @param {object} state - Mutable state object {current, answers}
     */
    Quizzes.renderQuiz = function (quizData, state) {
        var questions = quizData.questions || [];
        state = state || { current: 0, answers: {} };

        // Build navigation panel
        var nav = document.getElementById('q-nav');
        if (nav) {
            nav.innerHTML = '';
            questions.forEach(function (_, i) {
                var btn = document.createElement('button');
                btn.className = 'h-8 w-8 rounded-lg border text-xs font-medium transition';
                btn.textContent = i + 1;
                btn.setAttribute('aria-label', 'Question ' + (i + 1));
                btn.addEventListener('click', function () { state.current = i; _showQuestion(questions, state, nav); });
                nav.appendChild(btn);
            });
        }

        _showQuestion(questions, state, nav);
    };

    // ── Render Individual Question ──────────────────────────────────────
    /**
     * Render a single question based on its type.
     * @param {object} question
     * @param {number} index
     * @param {object} answers - Current answers map
     * @param {Function} onChange - Called when answer changes
     */
    Quizzes.renderQuestion = function (question, index, answers, onChange) {
        var q = question;
        var stem = document.getElementById('q-stem');
        var choicesEl = document.getElementById('q-choices');
        var inputArea = document.getElementById('q-input-area');
        var textInput = document.getElementById('q-text-input');

        // Question header
        var numEl = document.getElementById('q-number');
        if (numEl) numEl.textContent = 'Question ' + (index + 1);
        var typeEl = document.getElementById('q-type-badge');
        if (typeEl) typeEl.textContent = (q.type || 'multiple_choice').replace(/_/g, ' ');

        // Stem
        if (stem) SchoolAI.Markdown.renderElement(stem, q.question || q.stem || '');

        // Clear previous
        if (choicesEl) choicesEl.innerHTML = '';
        if (inputArea) inputArea.classList.add('hidden');

        var type = q.type || 'multiple_choice_4';

        // Short answer / numeric entry / fill in the blank
        if (type === 'short_answer' || type === 'numeric_entry' || type === 'fill_in_the_blank') {
            if (inputArea) inputArea.classList.remove('hidden');
            if (textInput) {
                textInput.value = answers[index] || '';
                textInput.type = type === 'numeric_entry' ? 'number' : 'text';
                textInput.placeholder = type === 'fill_in_the_blank' ? 'Fill in the blank…' : 'Type your answer…';
                textInput.oninput = function () {
                    answers[index] = textInput.value;
                    if (typeof onChange === 'function') onChange();
                };
            }
            return;
        }

        // Multiple choice or multi-select
        var isMulti = type === 'multi_select';
        var choices = q.choices || q.options || [];

        choices.forEach(function (opt, i) {
            var selected = isMulti ? (Array.isArray(answers[index]) && answers[index].indexOf(i) !== -1) : answers[index] === i;
            var label = document.createElement('label');
            label.className = 'flex items-center gap-3 rounded-lg border p-4 cursor-pointer transition ' +
                (selected ? 'border-primary-500 bg-primary-50' : 'border-neutral-200 hover:bg-neutral-50');

            var optText = typeof opt === 'string' ? opt : (opt.text || '');
            label.innerHTML =
                '<input type="' + (isMulti ? 'checkbox' : 'radio') + '" name="q' + index + '" value="' + i + '" ' + (selected ? 'checked' : '') + ' class="sr-only">' +
                '<span class="flex h-6 w-6 items-center justify-center rounded-full border-2 text-xs font-semibold ' +
                (selected ? 'border-primary-600 bg-primary-600 text-white' : 'border-neutral-300 text-neutral-500') + '">' +
                String.fromCharCode(65 + i) + '</span>' +
                '<span class="text-sm text-neutral-700">' + _esc(optText) + '</span>';

            label.addEventListener('click', function (e) {
                e.preventDefault();
                if (isMulti) {
                    if (!Array.isArray(answers[index])) answers[index] = [];
                    var idx = answers[index].indexOf(i);
                    if (idx >= 0) answers[index].splice(idx, 1);
                    else answers[index].push(i);
                } else {
                    answers[index] = i;
                }
                if (typeof onChange === 'function') onChange();
            });

            if (choicesEl) choicesEl.appendChild(label);
        });
    };

    // ── Collect Answers ─────────────────────────────────────────────────
    /**
     * Gather user answers from the current state.
     * @param {object} answers - Answers map
     * @returns {object}
     */
    Quizzes.collectAnswers = function (answers) {
        return Object.assign({}, answers);
    };

    // ── Grade Quiz ──────────────────────────────────────────────────────
    /**
     * Submit answers for grading.
     * @param {string} quizId
     * @param {object} answers
     * @returns {Promise<object>}
     */
    Quizzes.gradeQuiz = function (quizId, answers) {
        return SchoolAI.API.post('/api/quizzes/' + encodeURIComponent(quizId) + '/grade', { answers: answers });
    };

    // ── Render Review ───────────────────────────────────────────────────
    /**
     * Render quiz review with score and per-question results.
     * @param {object} quizData
     * @param {object} results
     */
    Quizzes.renderReview = function (quizData, results) {
        var questions = quizData.questions || [];
        var container = document.getElementById('questions-review');
        if (!container) return;

        questions.forEach(function (q, i) {
            var userAnswer = results.answers ? results.answers[i] : undefined;
            var correctAnswer = q.correctAnswer != null ? q.correctAnswer : q.answer;
            var isCorrect = JSON.stringify(userAnswer) === JSON.stringify(correctAnswer);

            var div = document.createElement('div');
            div.className = 'rounded-2xl bg-white p-6 shadow-sm ring-1 ' + (isCorrect ? 'ring-green-200' : 'ring-red-200');
            div.innerHTML =
                '<div class="flex items-start justify-between mb-3">' +
                '<span class="text-sm font-medium text-neutral-500">Question ' + (i + 1) + '</span>' +
                '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ' +
                (isCorrect ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700') + '">' +
                (isCorrect ? '✓ Correct' : '✗ Incorrect') + '</span></div>' +
                '<div class="question-stem text-neutral-900 mb-4"></div>' +
                (!isCorrect ? '<div class="rounded-lg bg-red-50 border border-red-200 p-3 mb-2"><p class="text-sm text-red-700"><strong>Your answer:</strong> ' + _esc(String(userAnswer != null ? userAnswer : 'No answer')) + '</p></div>' : '') +
                '<div class="rounded-lg bg-green-50 border border-green-200 p-3 mb-2"><p class="text-sm text-green-700"><strong>Correct answer:</strong> ' + _esc(String(correctAnswer != null ? correctAnswer : '')) + '</p></div>' +
                '<div class="explanation hidden mt-4 rounded-lg bg-primary-50 border border-primary-200 p-4 text-sm text-neutral-700"></div>';

            container.appendChild(div);
            SchoolAI.Markdown.renderElement(div.querySelector('.question-stem'), q.question || q.stem || '');
        });
    };

    // ── Generate Explanations ───────────────────────────────────────────
    /**
     * Request AI-generated explanations for quiz questions.
     * @param {string} quizId
     * @param {string[]} [questionIds] - Specific question IDs, or all if omitted
     * @returns {Promise<Array>}
     */
    Quizzes.generateExplanations = function (quizId, questionIds) {
        var payload = { quizId: quizId };
        if (questionIds) payload.questionIds = questionIds;

        return new Promise(function (resolve, reject) {
            SchoolAI.showAiModal({
                title: 'Generate Explanations',
                operation: 'explanation_generate',
                summary: 'Generating AI explanations for quiz questions.',
                onConfirm: function () {
                    SchoolAI.setAiModalLoading(true);
                    SchoolAI.API.post('/api/ai/explain', payload)
                        .then(function (data) {
                            SchoolAI.setAiModalLoading(false);
                            SchoolAI.hideAiModal(false);
                            resolve(data.explanations || data.data || []);
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
    };

    // ── Request Hint ────────────────────────────────────────────────────
    /**
     * Request an AI-generated hint for a specific question.
     * @param {string} quizId
     * @param {string} questionId
     * @returns {Promise<string>}
     */
    Quizzes.requestHint = function (quizId, questionId) {
        return new Promise(function (resolve, reject) {
            SchoolAI.showAiModal({
                title: 'Get Hint',
                operation: 'hint_generate',
                summary: 'Requesting a hint for this question.',
                onConfirm: function () {
                    SchoolAI.setAiModalLoading(true);
                    SchoolAI.API.post('/api/ai/hint', { quizId: quizId, questionId: questionId })
                        .then(function (data) {
                            SchoolAI.setAiModalLoading(false);
                            SchoolAI.hideAiModal(false);
                            resolve(data.hint || data.data || '');
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
    };

    // ── Internal ────────────────────────────────────────────────────────
    function _showQuestion(questions, state, nav) {
        var q = questions[state.current];
        if (!q) return;
        Quizzes.renderQuestion(q, state.current, state.answers, function () {
            _showQuestion(questions, state, nav);
        });
        _updateNav(nav, state);

        // Update prev/next buttons
        var prevBtn = document.getElementById('prev-btn');
        var nextBtn = document.getElementById('next-btn');
        var submitBtn = document.getElementById('submit-quiz-btn');
        if (prevBtn) prevBtn.disabled = state.current === 0;
        var isLast = state.current === questions.length - 1;
        if (nextBtn) nextBtn.classList.toggle('hidden', isLast);
        if (submitBtn) submitBtn.classList.toggle('hidden', !isLast);
    }

    function _updateNav(nav, state) {
        if (!nav) return;
        nav.querySelectorAll('button').forEach(function (btn, i) {
            var answered = state.answers[i] !== undefined && state.answers[i] !== '' &&
                (!Array.isArray(state.answers[i]) || state.answers[i].length > 0);
            var isCurrent = i === state.current;
            btn.className = 'h-8 w-8 rounded-lg border text-xs font-medium transition ' +
                (isCurrent ? 'border-primary-500 bg-primary-600 text-white' :
                 answered ? 'border-primary-300 bg-primary-100 text-primary-700' :
                 'border-neutral-200 text-neutral-500 hover:bg-neutral-50');
        });
    }

    function _esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }
})();
