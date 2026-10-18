/* Account tab: save / remove the key, refresh the plan data. */
(function () {
    'use strict';
    Dvx.ready(function () {
        var root = document.getElementById('davix-h2i-admin');
        if (!root) { return; }

        Dvx.on(root, 'click', '[data-dvx-save-connection]', function (e, btn) {
            var wrap = btn.closest('[data-dvx-connection]');
            var key = (wrap.querySelector('.dvs-connection-test__field') || {}).value || '';
            var body = { key: key.trim() };
            btn.disabled = true;
            Dvx.api('connection/save', { method: 'POST', body: body })
                .then(function (res) {
                    if (res && res.ok) {
                        Dvx.toast(res.message || 'Connected', 'success');
                        setTimeout(function () { window.location.reload(); }, 600);
                    } else {
                        Dvx.toast((res && res.message) || 'Could not connect', 'error');
                        btn.disabled = false;
                    }
                })
                .catch(function (err) { Dvx.toast(err.message, 'error'); btn.disabled = false; });
        });

        Dvx.on(root, 'click', '[data-dvx-remove-key]', function (e, btn) {
            if (!window.confirm(Dvx.i18n.confirm)) { return; }
            btn.disabled = true;
            Dvx.api('connection/remove', { method: 'POST' }).then(function () { window.location.reload(); });
        });

        Dvx.on(root, 'click', '[data-dvx-refresh-limits]', function (e, btn) {
            btn.disabled = true;
            Dvx.api('limits/refresh', { method: 'POST' }).then(function () { window.location.reload(); }).catch(function (err) { Dvx.toast(err.message, 'error'); btn.disabled = false; });
        });
    });
})();
