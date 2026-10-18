/* "?" help bubbles: [data-dvx-tip] shows its text in the shared tooltip bubble. */
(function () {
    'use strict';
    var bubble;

    function ensure() {
        if (!bubble) {
            bubble = document.createElement('div');
            bubble.className = 'davix-h2i-tooltip-bubble';
            bubble.setAttribute('role', 'tooltip');
            bubble.setAttribute('data-state', 'hidden');
            document.body.appendChild(bubble);
        }
        return bubble;
    }

    function show(btn) {
        var b = ensure();
        b.textContent = btn.getAttribute('data-dvx-tip');
        b.setAttribute('data-state', 'visible');
        var r = btn.getBoundingClientRect();
        var left = Math.min(window.innerWidth - b.offsetWidth - 12, Math.max(12, r.left + window.scrollX - 8));
        b.style.left = left + 'px';
        b.style.top = (r.bottom + window.scrollY + 8) + 'px';
        b.style.position = 'absolute';
    }

    function hide() { if (bubble) { bubble.setAttribute('data-state', 'hidden'); } }

    Dvx.ready(function () {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-dvx-tip]');
            if (btn) { e.preventDefault(); show(btn); } else { hide(); }
        });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { hide(); } });
        document.addEventListener('mouseover', function (e) {
            var btn = e.target.closest ? e.target.closest('[data-dvx-tip]') : null;
            if (btn) { show(btn); }
        });
        document.addEventListener('mouseout', function (e) {
            if (e.target.closest && e.target.closest('[data-dvx-tip]')) { hide(); }
        });
    });
})();
