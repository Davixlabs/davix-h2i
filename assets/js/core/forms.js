/* Settings forms: [data-dvx-form data-section] saves through the REST API; [data-dvx-reset] restores the section defaults;
   rows with data-dvx-requires="field=value" show only while that field has that value. */
(function () {
    'use strict';

    function setBusy(form, busy) {
        Dvx.qsa('.dvs-save-button, [data-dvx-reset]', form).forEach(function (b) { b.disabled = busy; });
        form.classList.toggle('is-busy', busy);
    }

    function applyRequires(root) {
        Dvx.qsa('[data-dvx-requires]', root).forEach(function (row) {
            var rule = row.getAttribute('data-dvx-requires').split('=');
            var form = row.closest('form') || document;
            var field = form.querySelector('[name$="' + rule[0] + '"]:not([type=hidden]), [name$="' + rule[0] + '"][type=hidden]:last-of-type');
            var all = Dvx.qsa('[name$="' + rule[0] + '"]', form);
            var current = '';
            all.forEach(function (f) {
                if ((f.type === 'checkbox' || f.type === 'radio') && !f.checked) { return; }
                current = f.value;
            });
            if (!field) { return; }
            var want = rule.length > 1 ? rule[1] : '1';
            var show = want.split('|').indexOf(current) > -1;
            row.classList.toggle('dvx-hidden', !show);
        });
    }

    Dvx.ready(function () {
        var root = document.getElementById('davix-h2i-admin');
        if (!root) { return; }

        applyRequires(root);
        root.addEventListener('change', function () { applyRequires(root); });

        root.addEventListener('submit', function (e) {
            var form = e.target.closest('[data-dvx-form]');
            if (!form) { return; }
            e.preventDefault();
            var section = form.getAttribute('data-section');
            var all = Dvx.formData(form);
            var values = all[section] || {};
            setBusy(form, true);
            Dvx.api('settings/' + section, { method: 'POST', body: values })
                .then(function () { Dvx.toast(Dvx.i18n.saved || 'Saved', 'success'); form.dispatchEvent(new CustomEvent('dvx:saved', { bubbles: true })); })
                .catch(function (err) { Dvx.toast((err && err.message) || Dvx.i18n.saveFailed, 'error'); })
                .then(function () { setBusy(form, false); });
        });

        Dvx.on(root, 'click', '[data-dvx-reset]', function (e, btn) {
            var form = btn.closest('[data-dvx-form]');
            if (!form || !window.confirm(Dvx.i18n.confirm || 'Are you sure?')) { return; }
            setBusy(form, true);
            Dvx.api('settings/' + form.getAttribute('data-section') + '/reset', { method: 'POST' })
                .then(function () { window.location.reload(); })
                .catch(function (err) { Dvx.toast(err.message, 'error'); setBusy(form, false); });
        });
    });
})();
