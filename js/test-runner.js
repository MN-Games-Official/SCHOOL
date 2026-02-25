/**
 * test-runner.js – Standardized Test Runner (CRITICAL)
 *
 * SchoolAI.TestRunner class manages exam execution with:
 * - Section-by-section flow with start pages
 * - Fullscreen enforcement with pause/resume
 * - Countdown timer with MM:SS display
 * - Question navigation with flagging
 * - Visibility and blur detection
 * - Integrity event logging
 *
 * @namespace SchoolAI.TestRunner
 */
(function () {
    'use strict';

    window.SchoolAI = window.SchoolAI || {};

    /**
     * @constructor
     * @param {object} examData - Full exam data with sections and questions
     */
    function TestRunner(examData) {
        this.examData = examData || {};
        this.sections = examData.sections || [];
        this.currentSection = -1;
        this.currentQuestion = 0;
        this.answers = {};       // { sectionIdx: { questionIdx: answer } }
        this.flags = {};         // { sectionIdx: { questionIdx: true } }
        this.integrityLog = [];
        this.timerInterval = null;
        this.timerRemaining = 0;
        this.timerPaused = false;
        this.sectionScores = [];
        this._boundVisibility = this._onVisibilityChange.bind(this);
        this._boundFullscreen = this._onFullscreenChange.bind(this);
        this._boundBlur = this._onWindowBlur.bind(this);
        this._boundFocus = this._onWindowFocus.bind(this);
    }

    // ── Start Exam ──────────────────────────────────────────────────────
    TestRunner.prototype.start = function () {
        this.logEvent('exam_start', { timestamp: Date.now() });
        this.startSection(0);
    };

    // ── Section Start Page ──────────────────────────────────────────────
    /**
     * Show section start page with info and "Begin Section" button.
     * @param {number} sectionIndex
     */
    TestRunner.prototype.startSection = function (sectionIndex) {
        if (sectionIndex >= this.sections.length) {
            this.finishExam();
            return;
        }

        this.currentSection = sectionIndex;
        this.currentQuestion = 0;
        if (!this.answers[sectionIndex]) this.answers[sectionIndex] = {};
        if (!this.flags[sectionIndex]) this.flags[sectionIndex] = {};

        var section = this.sections[sectionIndex];
        var questions = section.questions || [];

        // Update UI
        var startPage = document.getElementById('section-start');
        var questionArea = document.getElementById('exam-question-area');
        var title = document.getElementById('section-start-title');
        var info = document.getElementById('section-start-info');
        var startBtn = document.getElementById('section-start-btn');

        if (questionArea) questionArea.classList.add('hidden');
        if (startPage) startPage.classList.remove('hidden');
        if (title) title.textContent = section.name || ('Section ' + (sectionIndex + 1));
        if (info) {
            info.innerHTML =
                '<span class="block text-lg font-semibold mt-2">' + questions.length + ' questions · ' + (section.time || section.timeLimit || '--') + ' minutes</span>' +
                (section.tools ? '<span class="block mt-1 text-sm text-neutral-500">Allowed tools: ' + _esc(section.tools) + '</span>' : '');
        }

        var self = this;
        if (startBtn) {
            var clone = startBtn.cloneNode(true);
            startBtn.parentNode.replaceChild(clone, startBtn);
            clone.addEventListener('click', function () { self.beginSection(); });
        }
    };

    // ── Begin Section ───────────────────────────────────────────────────
    TestRunner.prototype.beginSection = function () {
        var section = this.sections[this.currentSection];
        if (!section) return;

        this.logEvent('section_start', { section: this.currentSection, name: section.name });

        // Hide start page, show question area
        var startPage = document.getElementById('section-start');
        var questionArea = document.getElementById('exam-question-area');
        if (startPage) startPage.classList.add('hidden');
        if (questionArea) questionArea.classList.remove('hidden');

        // Update section title
        var sectionTitle = document.getElementById('exam-section-title');
        if (sectionTitle) sectionTitle.textContent = section.name || ('Section ' + (this.currentSection + 1));

        // Build nav
        this._buildNav();

        // Start timer
        var timeMinutes = section.time || section.timeLimit || 30;
        this.startTimer(timeMinutes * 60);

        // Enter fullscreen
        this.enterFullscreen();
        this._bindEvents();

        // Show first question
        this.showQuestion(0);
    };

    // ── Timer Management ────────────────────────────────────────────────
    TestRunner.prototype.startTimer = function (seconds) {
        this.timerRemaining = seconds;
        this.timerPaused = false;
        var self = this;

        this._updateTimerDisplay();
        clearInterval(this.timerInterval);
        this.timerInterval = setInterval(function () {
            if (self.timerPaused) return;
            self.timerRemaining--;
            self._updateTimerDisplay();
            if (self.timerRemaining <= 0) {
                clearInterval(self.timerInterval);
                SchoolAI.toast('Time\'s up for this section!', 'warning');
                self.submitSection();
            }
        }, 1000);
    };

    TestRunner.prototype.pauseTimer = function () {
        this.timerPaused = true;
        this.logEvent('timer_pause', { remaining: this.timerRemaining });
    };

    TestRunner.prototype.resumeTimer = function () {
        this.timerPaused = false;
        this.logEvent('timer_resume', { remaining: this.timerRemaining });
    };

    TestRunner.prototype._updateTimerDisplay = function () {
        var display = document.getElementById('exam-time-display');
        if (!display) return;
        var m = Math.floor(Math.max(0, this.timerRemaining) / 60);
        var s = Math.max(0, this.timerRemaining) % 60;
        display.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    };

    // ── Fullscreen Enforcement ──────────────────────────────────────────
    TestRunner.prototype.enterFullscreen = function () {
        var el = document.documentElement;
        if (el.requestFullscreen) el.requestFullscreen().catch(function () {});
        else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
    };

    TestRunner.prototype._bindEvents = function () {
        document.addEventListener('fullscreenchange', this._boundFullscreen);
        document.addEventListener('webkitfullscreenchange', this._boundFullscreen);
        document.addEventListener('visibilitychange', this._boundVisibility);
        window.addEventListener('blur', this._boundBlur);
        window.addEventListener('focus', this._boundFocus);
    };

    TestRunner.prototype._unbindEvents = function () {
        document.removeEventListener('fullscreenchange', this._boundFullscreen);
        document.removeEventListener('webkitfullscreenchange', this._boundFullscreen);
        document.removeEventListener('visibilitychange', this._boundVisibility);
        window.removeEventListener('blur', this._boundBlur);
        window.removeEventListener('focus', this._boundFocus);
    };

    TestRunner.prototype._onFullscreenChange = function () {
        if (!document.fullscreenElement && !document.webkitFullscreenElement) {
            this._showPauseOverlay();
        } else {
            this._hidePauseOverlay();
        }
    };

    TestRunner.prototype._onVisibilityChange = function () {
        if (document.hidden) {
            this._showPauseOverlay();
            this.logEvent('visibility_hidden', { timestamp: Date.now() });
        }
    };

    TestRunner.prototype._onWindowBlur = function () {
        this._showPauseOverlay();
        this.logEvent('window_blur', { timestamp: Date.now() });
    };

    TestRunner.prototype._onWindowFocus = function () {
        // Only resume if in fullscreen
        if (document.fullscreenElement || document.webkitFullscreenElement) {
            this._hidePauseOverlay();
        }
    };

    TestRunner.prototype._showPauseOverlay = function () {
        var overlay = document.getElementById('exam-pause-overlay');
        if (overlay) overlay.classList.remove('hidden');
        this.pauseTimer();
        this._setInputsDisabled(true);
        this.logEvent('pause', { timestamp: Date.now(), reason: 'focus_lost' });
    };

    TestRunner.prototype._hidePauseOverlay = function () {
        var overlay = document.getElementById('exam-pause-overlay');
        if (overlay) overlay.classList.add('hidden');
        this.resumeTimer();
        this._setInputsDisabled(false);
        this.logEvent('resume', { timestamp: Date.now() });
    };

    TestRunner.prototype._setInputsDisabled = function (disabled) {
        var area = document.getElementById('exam-question-area');
        if (!area) return;
        area.querySelectorAll('input, button, label').forEach(function (el) {
            if (el.id !== 'exam-resume-btn') el.disabled = disabled;
            if (disabled) el.style.pointerEvents = 'none';
            else el.style.pointerEvents = '';
        });
    };

    // ── Question Navigation ─────────────────────────────────────────────
    TestRunner.prototype.showQuestion = function (index) {
        var section = this.sections[this.currentSection];
        if (!section) return;
        var questions = section.questions || [];
        if (index < 0 || index >= questions.length) return;

        this.currentQuestion = index;
        var q = questions[index];
        var answers = this.answers[this.currentSection];

        // Update header
        var numEl = document.getElementById('exam-q-number');
        if (numEl) numEl.textContent = 'Question ' + (index + 1) + ' of ' + questions.length;
        var typeEl = document.getElementById('exam-q-type');
        if (typeEl) typeEl.textContent = (q.type || 'multiple_choice').replace(/_/g, ' ');
        var progressEl = document.getElementById('exam-progress');
        if (progressEl) progressEl.textContent = 'Section ' + (this.currentSection + 1) + ' of ' + this.sections.length;

        // Render stem
        var stem = document.getElementById('exam-q-stem');
        if (stem) SchoolAI.Markdown.renderElement(stem, q.question || q.stem || '');

        // Render choices
        var choicesEl = document.getElementById('exam-q-choices');
        var inputArea = document.getElementById('exam-q-input');
        var textInput = document.getElementById('exam-q-text-input');
        if (choicesEl) choicesEl.innerHTML = '';
        if (inputArea) inputArea.classList.add('hidden');

        var type = q.type || 'multiple_choice_4';
        var self = this;

        if (type === 'short_answer' || type === 'numeric_entry' || type === 'fill_in_the_blank') {
            if (inputArea) inputArea.classList.remove('hidden');
            if (textInput) {
                textInput.value = answers[index] || '';
                textInput.type = type === 'numeric_entry' ? 'number' : 'text';
                textInput.oninput = function () {
                    var prev = answers[index];
                    answers[index] = textInput.value;
                    self.logEvent('answer_change', { section: self.currentSection, question: index, from: prev, to: textInput.value });
                    self._updateNav();
                };
            }
        } else {
            var isMulti = type === 'multi_select';
            var choices = q.choices || q.options || [];
            choices.forEach(function (opt, i) {
                var selected = isMulti ? (Array.isArray(answers[index]) && answers[index].indexOf(i) !== -1) : answers[index] === i;
                var label = document.createElement('label');
                label.className = 'flex items-center gap-3 rounded-lg border p-4 cursor-pointer transition ' +
                    (selected ? 'border-primary-500 bg-primary-50' : 'border-neutral-200 hover:bg-neutral-50');
                var optText = typeof opt === 'string' ? opt : (opt.text || '');
                label.innerHTML =
                    '<input type="' + (isMulti ? 'checkbox' : 'radio') + '" name="eq' + index + '" value="' + i + '" ' + (selected ? 'checked' : '') + ' class="sr-only">' +
                    '<span class="flex h-6 w-6 items-center justify-center rounded-full border-2 text-xs font-semibold ' +
                    (selected ? 'border-primary-600 bg-primary-600 text-white' : 'border-neutral-300 text-neutral-500') + '">' +
                    String.fromCharCode(65 + i) + '</span>' +
                    '<span class="text-sm text-neutral-700">' + _esc(optText) + '</span>';
                label.addEventListener('click', function (e) {
                    e.preventDefault();
                    var prev = answers[index];
                    if (isMulti) {
                        if (!Array.isArray(answers[index])) answers[index] = [];
                        var idx = answers[index].indexOf(i);
                        if (idx >= 0) answers[index].splice(idx, 1);
                        else answers[index].push(i);
                    } else {
                        answers[index] = i;
                    }
                    self.logEvent('answer_change', { section: self.currentSection, question: index, from: prev, to: answers[index] });
                    self.showQuestion(index);
                });
                if (choicesEl) choicesEl.appendChild(label);
            });
        }

        // Prev/Next/Finish buttons
        var prevBtn = document.getElementById('exam-prev');
        var nextBtn = document.getElementById('exam-next');
        var finishBtn = document.getElementById('exam-finish-section');
        if (prevBtn) prevBtn.disabled = index === 0;
        var isLast = index === questions.length - 1;
        if (nextBtn) nextBtn.classList.toggle('hidden', isLast);
        if (finishBtn) finishBtn.classList.toggle('hidden', !isLast);

        // Wire nav buttons (replace to avoid duplicate listeners)
        this._wireButton('exam-prev', function () { self.prevQuestion(); });
        this._wireButton('exam-next', function () { self.nextQuestion(); });
        this._wireButton('exam-finish-section', function () { self.submitSection(); });

        this._updateNav();
    };

    TestRunner.prototype.nextQuestion = function () {
        var section = this.sections[this.currentSection];
        if (section && this.currentQuestion < (section.questions || []).length - 1) {
            this.showQuestion(this.currentQuestion + 1);
        }
    };

    TestRunner.prototype.prevQuestion = function () {
        if (this.currentQuestion > 0) {
            this.showQuestion(this.currentQuestion - 1);
        }
    };

    /** Toggle flag on a question. */
    TestRunner.prototype.flagQuestion = function (index) {
        var sectionFlags = this.flags[this.currentSection];
        if (!sectionFlags) return;
        sectionFlags[index] = !sectionFlags[index];
        this.logEvent('flag', { section: this.currentSection, question: index, flagged: sectionFlags[index] });
        this._updateNav();
    };

    // ── Navigation Panel ────────────────────────────────────────────────
    TestRunner.prototype._buildNav = function () {
        var nav = document.getElementById('exam-q-nav');
        if (!nav) return;
        nav.innerHTML = '';
        var section = this.sections[this.currentSection];
        var questions = section ? (section.questions || []) : [];
        var self = this;

        questions.forEach(function (_, i) {
            var btn = document.createElement('button');
            btn.className = 'h-8 w-8 rounded-lg border text-xs font-medium transition';
            btn.textContent = i + 1;
            btn.setAttribute('aria-label', 'Question ' + (i + 1));
            btn.addEventListener('click', function () { self.showQuestion(i); });
            btn.addEventListener('contextmenu', function (e) {
                e.preventDefault();
                self.flagQuestion(i);
            });
            nav.appendChild(btn);
        });
    };

    TestRunner.prototype._updateNav = function () {
        var nav = document.getElementById('exam-q-nav');
        if (!nav) return;
        var answers = this.answers[this.currentSection] || {};
        var sectionFlags = this.flags[this.currentSection] || {};
        var self = this;

        nav.querySelectorAll('button').forEach(function (btn, i) {
            var answered = answers[i] !== undefined && answers[i] !== '' &&
                (!Array.isArray(answers[i]) || answers[i].length > 0);
            var flagged = !!sectionFlags[i];
            var isCurrent = i === self.currentQuestion;

            btn.className = 'h-8 w-8 rounded-lg border text-xs font-medium transition ' +
                (flagged ? 'border-amber-500 bg-amber-100 text-amber-700' :
                 isCurrent ? 'border-primary-500 bg-primary-600 text-white' :
                 answered ? 'border-primary-300 bg-primary-100 text-primary-700' :
                 'border-neutral-200 text-neutral-500 hover:bg-neutral-50');
        });
    };

    // ── Submit Section ──────────────────────────────────────────────────
    TestRunner.prototype.submitSection = function () {
        clearInterval(this.timerInterval);
        this.logEvent('section_end', { section: this.currentSection, remaining: this.timerRemaining });

        var sectionData = this.sections[this.currentSection];
        var sectionAnswers = this.answers[this.currentSection] || {};

        // Grade locally if correct answers available, otherwise defer to server
        var score = this._gradeSection(sectionData, sectionAnswers);
        this.sectionScores.push({ section: this.currentSection, score: score });

        this._unbindEvents();

        // Move to next section or finish
        if (this.currentSection < this.sections.length - 1) {
            this.startSection(this.currentSection + 1);
        } else {
            this.finishExam();
        }
    };

    TestRunner.prototype._gradeSection = function (sectionData, answers) {
        var questions = sectionData ? (sectionData.questions || []) : [];
        var correct = 0;
        questions.forEach(function (q, i) {
            var correctAnswer = q.correctAnswer != null ? q.correctAnswer : q.answer;
            if (correctAnswer != null && JSON.stringify(answers[i]) === JSON.stringify(correctAnswer)) {
                correct++;
            }
        });
        return questions.length ? Math.round((correct / questions.length) * 100) : 0;
    };

    // ── Finish Exam ─────────────────────────────────────────────────────
    TestRunner.prototype.finishExam = function () {
        clearInterval(this.timerInterval);
        this._unbindEvents();

        // Exit fullscreen
        if (document.exitFullscreen) document.exitFullscreen().catch(function () {});

        this.logEvent('exam_end', { timestamp: Date.now(), scores: this.sectionScores });

        // Calculate overall score
        var total = 0;
        this.sectionScores.forEach(function (s) { total += s.score; });
        var avg = this.sectionScores.length ? Math.round(total / this.sectionScores.length) : 0;

        // Show results
        var questionArea = document.getElementById('exam-question-area');
        var startPage = document.getElementById('section-start');
        if (questionArea) questionArea.classList.add('hidden');
        if (startPage) {
            startPage.classList.remove('hidden');
            var title = document.getElementById('section-start-title');
            var info = document.getElementById('section-start-info');
            var btn = document.getElementById('section-start-btn');
            if (title) title.textContent = 'Exam Complete!';
            if (info) {
                info.innerHTML = '<span class="block text-4xl font-bold text-primary-600 mt-4">' + avg + '%</span>' +
                    '<span class="block text-neutral-600 mt-2">Overall Score</span>' +
                    this.sectionScores.map(function (s, i) {
                        return '<span class="block text-sm text-neutral-500 mt-1">Section ' + (i + 1) + ': ' + s.score + '%</span>';
                    }).join('');
            }
            if (btn) {
                btn.textContent = 'View Review';
                var examId = this.examData.id || this.examData.examId;
                var clone = btn.cloneNode(true);
                btn.parentNode.replaceChild(clone, btn);
                clone.addEventListener('click', function () {
                    if (examId) window.location.href = '/exams/review/' + encodeURIComponent(examId);
                    else window.location.href = '/exams';
                });
            }
        }

        SchoolAI.toast('Exam completed! Score: ' + avg + '%', 'success');
    };

    // ── Integrity Logging ───────────────────────────────────────────────
    /**
     * Log an integrity event.
     * @param {string} type - Event type
     * @param {object} data - Event data
     */
    TestRunner.prototype.logEvent = function (type, data) {
        this.integrityLog.push({
            type: type,
            timestamp: Date.now(),
            data: data || {}
        });
    };

    /** Get the full integrity log. */
    TestRunner.prototype.getIntegrityLog = function () {
        return this.integrityLog.slice();
    };

    // ── Internal helpers ────────────────────────────────────────────────
    /** Replace a button to clear old listeners. */
    TestRunner.prototype._wireButton = function (id, handler) {
        var btn = document.getElementById(id);
        if (!btn) return;
        var clone = btn.cloneNode(true);
        btn.parentNode.replaceChild(clone, btn);
        clone.addEventListener('click', handler);
    };

    function _esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    SchoolAI.TestRunner = TestRunner;
})();
