/**
 * api.js – API Client
 *
 * Provides SchoolAI.API.get, .post, and .aiRequest helpers.
 * Handles errors gracefully and shows toast on network failures.
 *
 * @namespace SchoolAI.API
 */
(function () {
    'use strict';

    window.SchoolAI = window.SchoolAI || {};
    var API = SchoolAI.API = {};

    // ── GET ─────────────────────────────────────────────────────────────
    /**
     * Send a GET request, returning parsed JSON.
     * @param {string} url
     * @returns {Promise<any>}
     */
    API.get = function (url) {
        return fetch(url, {
            headers: { 'X-CSRF-Token': SchoolAI.getCsrfToken() }
        })
            .then(_handleResponse)
            .catch(_handleError);
    };

    // ── POST ────────────────────────────────────────────────────────────
    /**
     * Send a POST request with CSRF token, returning parsed JSON.
     * @param {string} url
     * @param {object} data
     * @returns {Promise<any>}
     */
    API.post = function (url, data) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': SchoolAI.getCsrfToken()
            },
            body: JSON.stringify(data)
        })
            .then(_handleResponse)
            .catch(_handleError);
    };

    // ── AI Request ──────────────────────────────────────────────────────
    /**
     * Send an AI request through the gateway.
     * Shows the AI modal first for user confirmation, then posts to /api/ai/request.
     *
     * @param {string} operation  - Operation identifier
     * @param {object} params     - Parameters including messages, model, seed, summary
     * @returns {Promise<any>}
     */
    API.aiRequest = function (operation, params) {
        params = params || {};

        return new Promise(function (resolve, reject) {
            SchoolAI.showAiModal({
                title: 'AI Request: ' + operation,
                operation: operation,
                model: params.model || 'default',
                seed: params.seed,
                summary: params.summary || 'Sending ' + operation + ' request to AI.',
                onConfirm: function () {
                    SchoolAI.setAiModalLoading(true);

                    API.post('/api/ai/request', {
                        operation: operation,
                        messages: params.messages || [],
                        model: params.model,
                        seed: params.seed
                    })
                        .then(function (data) {
                            SchoolAI.setAiModalLoading(false);
                            SchoolAI.hideAiModal(false);
                            resolve(data);
                        })
                        .catch(function (err) {
                            SchoolAI.setAiModalLoading(false);
                            SchoolAI.hideAiModal(false);
                            reject(err);
                        });
                },
                onCancel: function () {
                    reject(new Error('AI request cancelled by user'));
                }
            });
        });
    };

    // ── Internal helpers ────────────────────────────────────────────────
    function _handleResponse(res) {
        if (!res.ok) {
            return res.json().catch(function () { return {}; }).then(function (body) {
                var msg = (body && body.error) || 'Request failed (' + res.status + ')';
                throw new Error(msg);
            });
        }
        return res.json();
    }

    function _handleError(err) {
        var msg = (err && err.message) || 'Network error';
        if (SchoolAI.toast) SchoolAI.toast(msg, 'error');
        throw err;
    }
})();
