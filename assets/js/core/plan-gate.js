/* Plan awareness in the browser: elements marked data-dvx-op="<operation id>" are greyed out (with an Upgrade badge) when
   the plan does not include the operation, and get a Beta badge when the feature is not production-ready.
   The state comes from the server (Dvx.config.gate), which reads it from the plan data. Nothing is hard-coded here. */
(function () {
    'use strict';

    function apply(root) {
        var gate = (Dvx.config && Dvx.config.gate) || {};
        Dvx.qsa('[data-dvx-op]', root || document).forEach(function (el) {
            var id = el.getAttribute('data-dvx-op');
            var info = gate[id];
            if (!info || el.getAttribute('data-dvx-gated') === '1') { return; }
            el.setAttribute('data-dvx-gated', '1');
            if (info.state === 'locked' || info.state === 'unavailable') {
                el.classList.add('is-locked');
                if ('disabled' in el) { el.disabled = true; }
                el.setAttribute('aria-disabled', 'true');
                if (Dvx.config.showUpgrade !== false || info.state === 'unavailable') {
                    var badge = document.createElement('span');
                    badge.className = 'dvs-badge dvs-badge--warning';
                    badge.textContent = info.state === 'unavailable' ? 'Unavailable' : (Dvx.i18n.upgrade || 'Upgrade');
                    var title = el.querySelector('.dvx-op__title') || el;
                    title.appendChild(badge);
                }
            } else if (info.state === 'beta') {
                var b = document.createElement('span');
                b.className = 'dvs-badge dvs-badge--info';
                b.textContent = Dvx.i18n.beta || 'Beta';
                (el.querySelector('.dvx-op__title') || el).appendChild(b);
            }
        });
    }

    Dvx.ready(function () { apply(document); });
    window.DvxGate = {
        apply: apply,
        state: function (id) { var g = (Dvx.config.gate || {})[id]; return g ? g.state : 'unknown'; },
        allowed: function (id) { var s = window.DvxGate.state(id); return s !== 'locked' && s !== 'unavailable'; },
        info: function (id) { return (Dvx.config.gate || {})[id] || null; }
    };
})();
