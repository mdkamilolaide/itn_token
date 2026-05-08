/**
 * itn_token — shared utilities for Vue 3-converted modules
 *
 * Loaded via system_structure.json's general_js (after common.js, after Vue).
 * Attaches a single `window.utils` namespace. Components in Vue 3-flipped
 * modules destructure from it at the top of each file:
 *
 *   const { ref, reactive, computed, watch, onMounted, onBeforeUnmount } = Vue;
 *   const { bus, fmt, displayDate, useFormat, useApp, safeMessage } = window.utils;
 *
 * Designed to coexist with the legacy Vue 2 stack — utils.js does NOT touch
 * `window.common`, `window.alert`, or `window.overlay`. Vue 2 pages still
 * load utils.js but typically don't reference it.
 */

(function (root) {
    'use strict';

    var utils = {};

    /* ------------------------------------------------------------------
     * Formatting
     * ------------------------------------------------------------------ */

    utils.displayDate = function (d) {
        if (!d) return '';
        var date = new Date(d);
        if (isNaN(date.getTime())) return '';
        return date.toLocaleString('en-us', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    utils.capitalize = function (word) {
        if (!word) return word;
        var lower = String(word).toLowerCase();
        return lower.charAt(0).toUpperCase() + lower.slice(1);
    };

    utils.capitalizeEachWords = function (text) {
        if (!text) return text;
        return String(text)
            .toLowerCase()
            .split(' ')
            .map(function (w) {
                return w.length ? w.charAt(0).toUpperCase() + w.slice(1) : w;
            })
            .join(' ');
    };

    utils.formatNumber = function (num) {
        var n = num ? parseInt(num, 10) : 0;
        return n ? n.toLocaleString() : 0;
    };

    /** Safe number formatter used everywhere defensive null-guards are needed. */
    utils.fmt = function (v) {
        return parseInt(v || 0).toLocaleString();
    };

    utils.checkIfEmpty = function (v) {
        return v === null || v === '' || v === undefined ? 'Nil' : v;
    };

    utils.convertStringNumberToFigures = function (d) {
        return utils.fmt(d);
    };

    /** Builds a filename-safe timestamp like "08-May-06_40_15_AM". */
    utils.formatTimestampForFilename = function () {
        var now = new Date();
        var pad2 = function (n) { return String(n).padStart(2, '0'); };
        var day = pad2(now.getDate());
        var month = now.toLocaleString('default', { month: 'short' });
        var hours = now.getHours();
        var minutes = pad2(now.getMinutes());
        var seconds = pad2(now.getSeconds());
        var gmt = hours >= 12 ? 'PM' : 'AM';
        hours = pad2(hours % 12 || 12);
        return day + '-' + month + '-' + hours + '_' + minutes + '_' + seconds + '_' + gmt;
    };

    /* ------------------------------------------------------------------
     * Input validators (preserving the keyboard handlers from Vue.mixin
     * in app-assets/app/smc/logistics/allocation.js)
     * ------------------------------------------------------------------ */

    var ALLOWED_NAV_KEYS = [
        'Backspace', 'Delete', 'ArrowLeft', 'ArrowRight',
        'Tab', 'Escape', 'Home', 'End',
    ];

    utils.numbersOnlyWithoutDot = function (event) {
        if (ALLOWED_NAV_KEYS.indexOf(event.key) !== -1) return;
        if (!/^\d$/.test(event.key)) {
            event.preventDefault();
        }
    };

    utils.validatePaste = function (event) {
        var pasteData = (event.clipboardData || window.clipboardData).getData('text');
        if (!/^\d+$/.test(pasteData)) {
            event.preventDefault();
        }
    };

    /* ------------------------------------------------------------------
     * API helpers — thin axios wrappers around the three service endpoints.
     * Reads URLs from window.common (already loaded by common.js).
     * ------------------------------------------------------------------ */

    utils.dataQuery = function (qid, params) {
        var url = (window.common && window.common.DataService) || '';
        return axios.get(url, { params: Object.assign({ qid: qid }, params || {}) });
    };

    utils.tableQuery = function (qid, params) {
        var url = (window.common && window.common.TableService) || '';
        return axios.get(url, { params: Object.assign({ qid: qid }, params || {}) });
    };

    utils.exportPost = function (qid, body) {
        var url = (window.common && window.common.ExportService) || '';
        return axios.post(url + '?qid=' + encodeURIComponent(qid), body);
    };

    /**
     * Extract the most useful error message from an axios rejection.
     * Server returns { result_code, message } JSON for 4xx; surface that
     * before falling back to error.message or String(error).
     */
    utils.safeMessage = function (err) {
        if (err && err.response && err.response.data && err.response.data.message) {
            return err.response.data.message;
        }
        if (err && err.message) return err.message;
        return String(err);
    };

    /* ------------------------------------------------------------------
     * Event bus — drop-in replacement for the Vue 2 `new Vue()` event-bus
     * pattern. Same event names (g-event-goto-page, g-event-update-user,
     * g-event-refresh-page, g-event-reset-form, g-event-update).
     *
     * Mini-mitt: ~25 LOC, no external dependency.
     * ------------------------------------------------------------------ */

    utils.bus = (function () {
        var all = new Map();
        return {
            on: function (type, handler) {
                var arr = all.get(type);
                if (!arr) { arr = []; all.set(type, arr); }
                arr.push(handler);
            },
            off: function (type, handler) {
                var arr = all.get(type);
                if (!arr) return;
                if (handler === undefined) {
                    all.delete(type);
                    return;
                }
                var i = arr.indexOf(handler);
                if (i >= 0) arr.splice(i, 1);
            },
            emit: function (type, evt) {
                var arr = all.get(type);
                if (!arr) return;
                arr.slice().forEach(function (h) { h(evt); });
            },
            clear: function () { all.clear(); },
        };
    })();

    /* ------------------------------------------------------------------
     * Composables — destructured by each component's setup() function.
     * Returns the SAME function names every Vue 2 component expected from
     * the global Vue.mixin, so a mechanical port can keep its templates
     * unchanged after returning these from setup().
     * ------------------------------------------------------------------ */

    utils.useFormat = function () {
        return {
            displayDate: utils.displayDate,
            capitalize: utils.capitalize,
            capitalizeEachWords: utils.capitalizeEachWords,
            formatNumber: utils.formatNumber,
            convertStringNumberToFigures: utils.convertStringNumberToFigures,
            fmt: utils.fmt,
            checkIfEmpty: utils.checkIfEmpty,
            numbersOnlyWithoutDot: utils.numbersOnlyWithoutDot,
            validatePaste: utils.validatePaste,
        };
    };

    /**
     * useEventBus({ onReset, onRefresh, onGoto, onUpdate, onUpdateUser })
     * Auto-registers each handler against the corresponding event name on
     * onMounted and unregisters on onBeforeUnmount. Pass any subset.
     */
    utils.useEventBus = function (handlers) {
        handlers = handlers || {};
        var Vue = root.Vue;
        if (!Vue || !Vue.onMounted || !Vue.onBeforeUnmount) {
            console.warn('[utils.useEventBus] Vue 3 lifecycle hooks not available. Did you load vue.global.js?');
            return;
        }
        var pairs = [];
        if (typeof handlers.onReset === 'function') pairs.push(['g-event-reset-form', handlers.onReset]);
        if (typeof handlers.onRefresh === 'function') pairs.push(['g-event-refresh-page', handlers.onRefresh]);
        if (typeof handlers.onGoto === 'function') pairs.push(['g-event-goto-page', handlers.onGoto]);
        if (typeof handlers.onUpdate === 'function') pairs.push(['g-event-update', handlers.onUpdate]);
        if (typeof handlers.onUpdateUser === 'function') pairs.push(['g-event-update-user', handlers.onUpdateUser]);

        Vue.onMounted(function () {
            pairs.forEach(function (p) { utils.bus.on(p[0], p[1]); });
        });
        Vue.onBeforeUnmount(function () {
            pairs.forEach(function (p) { utils.bus.off(p[0], p[1]); });
        });
    };

    /**
     * useApp(options) — boilerplate-free Vue 3 createApp wrapper.
     * Installs $bus, $displayDate, $formatNumber, $capitalize on the app's
     * globalProperties so any Options-API stragglers can still use them
     * via `this.$X`. Composition-API code uses `window.utils.X` directly.
     */
    utils.useApp = function (options) {
        var Vue = root.Vue;
        if (!Vue || typeof Vue.createApp !== 'function') {
            throw new Error('[utils.useApp] Vue 3 not loaded. Add this module to module_v3 in system_structure.json.');
        }
        var app = Vue.createApp(options || {});
        var g = app.config.globalProperties;
        g.$bus = utils.bus;
        g.$displayDate = utils.displayDate;
        g.$capitalize = utils.capitalize;
        g.$formatNumber = utils.formatNumber;
        g.$fmt = utils.fmt;
        g.$checkIfEmpty = utils.checkIfEmpty;
        return app;
    };

    /* ------------------------------------------------------------------
     * Bootstrap 4 modal a11y fix — Chrome's "Blocked aria-hidden on an
     * element because its descendant retained focus" warning.
     *
     * Multi-layer defense:
     *   1. MutationObserver — watches every aria-hidden="true" mutation
     *      anywhere in the document. If a descendant has focus when
     *      aria-hidden is applied, blur it synchronously. This is the
     *      guaranteed catch-all; it cannot be raced.
     *   2. $.fn.modal monkey-patch — every .modal('hide') blurs first.
     *   3. hide.bs.modal document listener — backstop for ESC /
     *      backdrop click / programmatic hide.
     *   4. Mousedown delegate on close buttons — blur before any
     *      click handler runs.
     *
     * Layer 1 is the only one Chrome itself can't beat. The others
     * are pre-emptive (they make the focus shift happen earlier).
     *
     * Applies to every Bootstrap modal (v2 and v3 stacks alike).
     * ------------------------------------------------------------------ */
    (function () {
        function blurActive() {
            var active = document.activeElement;
            if (active && active !== document.body && typeof active.blur === 'function') {
                try { active.blur(); } catch (e) { /* swallow */ }
            }
        }

        // Layer 1: MutationObserver. Runs immediately when aria-hidden is
        // set anywhere; if focus is trapped under it, blur synchronously.
        if (typeof MutationObserver !== 'undefined') {
            var observer = new MutationObserver(function (mutations) {
                for (var i = 0; i < mutations.length; i++) {
                    var m = mutations[i];
                    if (m.type !== 'attributes' || m.attributeName !== 'aria-hidden') continue;
                    var t = m.target;
                    if (!t || t.getAttribute('aria-hidden') !== 'true') continue;
                    var active = document.activeElement;
                    if (active && active !== document.body && t.contains(active)) {
                        try { active.blur(); } catch (e) { /* swallow */ }
                        try { document.body.focus(); } catch (e) { /* swallow */ }
                    }
                }
            });
            // Wait for body to exist before observing.
            function startObserver() {
                if (!document.body) {
                    document.addEventListener('DOMContentLoaded', startObserver);
                    return;
                }
                observer.observe(document.body, {
                    attributes: true,
                    subtree: true,
                    attributeFilter: ['aria-hidden'],
                });
            }
            startObserver();
        }

        function bind() {
            var $ = root.jQuery;
            if (!$ || !$.fn || !$.fn.modal) return;

            // Layer 2 (the strongest): patch Bootstrap's Constructor.prototype
            // ._hideModal. We do TWO things:
            //   (a) blur any trapped focus, synchronously
            //   (b) set the `inert` attribute on the modal element. inert
            //       is Chrome's recommended replacement for aria-hidden in
            //       this situation — it makes the subtree unfocusable AND
            //       hidden from assistive tech. Setting it BEFORE Bootstrap's
            //       setAttribute('aria-hidden', true) means any focus that
            //       would land on a modal descendant cannot, which removes
            //       the precondition for Chrome's warning.
            //
            // We pair it with a _showElement patch that removes inert when
            // the modal is shown again.
            var Ctor = $.fn.modal && $.fn.modal.Constructor;
            if (Ctor && Ctor.prototype && Ctor.prototype._hideModal && !Ctor.prototype._hideModal._itnPatched) {
                var originalHideModal = Ctor.prototype._hideModal;
                Ctor.prototype._hideModal = function () {
                    try {
                        var element = this._element;
                        if (element) {
                            var active = document.activeElement;
                            if (active && element.contains(active) && typeof active.blur === 'function') {
                                active.blur();
                                try { document.body.focus(); } catch (e) { /* swallow */ }
                            }
                            element.setAttribute('inert', '');
                        }
                    } catch (e) { /* swallow */ }
                    return originalHideModal.apply(this, arguments);
                };
                Ctor.prototype._hideModal._itnPatched = true;
            }
            if (Ctor && Ctor.prototype && Ctor.prototype._showElement && !Ctor.prototype._showElement._itnPatched) {
                var originalShowElement = Ctor.prototype._showElement;
                Ctor.prototype._showElement = function () {
                    try {
                        if (this._element) this._element.removeAttribute('inert');
                    } catch (e) { /* swallow */ }
                    return originalShowElement.apply(this, arguments);
                };
                Ctor.prototype._showElement._itnPatched = true;
            }

            // Layer 3: monkey-patch .modal() so every 'hide' call blurs early.
            if (!$.fn.modal._itnPatched) {
                var originalModal = $.fn.modal;
                $.fn.modal = function (action) {
                    if (action === 'hide') blurActive();
                    return originalModal.apply(this, arguments);
                };
                for (var k in originalModal) if (originalModal.hasOwnProperty(k)) $.fn.modal[k] = originalModal[k];
                $.fn.modal._itnPatched = true;
            }

            // Layer 4: hide.bs.modal at document level.
            $(document).on('hide.bs.modal', blurActive);

            // Layer 5: mousedown on dismiss/close buttons.
            $(document).on(
                'mousedown',
                '.modal [data-dismiss="modal"], .modal .close, .modal-header .close',
                blurActive
            );
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bind);
        } else {
            bind();
        }
    })();

    /* ------------------------------------------------------------------
     * Backwards-compat: leave window.common / window.alert / window.overlay
     * alone. They are loaded by common.js and remain the canonical legacy
     * API. Components are encouraged but not required to migrate to the
     * utils.* equivalents.
     * ------------------------------------------------------------------ */

    root.utils = utils;
})(window);
