/* API key test widget. Masked by default; the eye only reveals what is being typed now, never a saved key.
   The Test button sends the typed key (or tests the saved one when the field is empty); the circle remembers the last result
   for that key in localStorage (a hash of the typed key, never the key itself). */
(function () {
    'use strict';

    function hash(str) {
        var h = 5381;
        for (var i = 0; i < str.length; i++) { h = ((h << 5) + h) + str.charCodeAt(i); h |= 0; }
        return (h >>> 0).toString(36);
    }

    function init(widget) {
        var input = widget.querySelector('.dvs-connection-test__field');
        var eye = widget.querySelector('.dvs-connection-test__reveal');
        var btn = widget.querySelector('.dvs-connection-test__button');
        var dot = widget.querySelector('.dvs-connection-test__indicator');
        var msg = widget.querySelector('.dvs-connection-test__message');
        var stored = widget.getAttribute('data-has-stored') === '1';
        var testing = widget.getAttribute('data-testing-label') || 'Testing…';
        var missing = widget.getAttribute('data-missing-label') || 'Enter an API key first.';
        var storeKey = 'davixH2IConnTest:' + (widget.getAttribute('data-scope') || 'key') + ':';

        function fingerprint() { return input.value ? 'raw:' + hash(input.value) : (stored ? 'stored' : ''); }

        function set(state, label, showMessage) {
            dot.className = 'dvs-connection-test__indicator dvs-connection-test__indicator--' + state;
            dot.setAttribute('title', label || '');
            dot.setAttribute('aria-label', label || '');
            msg.textContent = showMessage ? label : '';
            msg.classList.toggle('dvs-connection-test__message--error', 'error' === state);
        }

        function remember(state, label) {
            var fp = fingerprint();
            if (!fp) { return; }
            try { window.localStorage.setItem(storeKey + fp, JSON.stringify({ state: state, label: label })); } catch (e) { /* storage may be blocked */ }
        }

        function restore() {
            var fp = fingerprint();
            if (fp) {
                try {
                    var saved = JSON.parse(window.localStorage.getItem(storeKey + fp) || 'null');
                    if (saved && saved.state) { set(saved.state, saved.label, false); return; }
                } catch (e) { /* ignore */ }
            }
            set('neutral', widget.getAttribute('data-neutral-label') || 'Not tested', false);
        }

        eye.addEventListener('click', function () {
            if (!input.value) { return; }
            var showing = 'text' === input.type;
            input.type = showing ? 'password' : 'text';
            eye.setAttribute('aria-pressed', showing ? 'false' : 'true');
            var icon = eye.querySelector('.dashicons');
            icon.classList.toggle('dashicons-visibility', showing);
            icon.classList.toggle('dashicons-hidden', !showing);
        });
        input.addEventListener('blur', function () {
            if ('text' === input.type) { eye.click(); }
        });
        input.addEventListener('input', restore);

        btn.addEventListener('click', function () {
            if (btn.disabled) { return; }
            var raw = input.value.trim();
            if (!raw && !stored) { set('error', missing, true); return; }
            btn.disabled = true;
            set('loading', testing, true);
            var body = {};
            if (raw) { body.raw_key = raw; }
            window.Dvx.api('connection/test', { method: 'POST', body: body })
                .then(function (res) {
                    var ok = !!(res && res.ok);
                    var label = (res && res.message) || (ok ? 'Connected' : 'Connection failed');
                    set(ok ? 'success' : 'error', label, true);
                    remember(ok ? 'success' : 'error', label);
                })
                .catch(function (err) { set('error', (err && err.message) || 'Connection failed', true); })
                .then(function () { btn.disabled = false; });
        });

        restore();
    }

    window.Dvx.ready(function () {
        Array.prototype.forEach.call(document.querySelectorAll('[data-dvx-connection-test]'), init);
    });
})();
