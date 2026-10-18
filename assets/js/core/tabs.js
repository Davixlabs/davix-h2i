/* Sidebar collapse (remembered per browser) and the mobile behaviour of the shell. */
(function () {
    'use strict';
    Dvx.ready(function () {
        var sidebar = document.getElementById('davix-h2i-sidebar');
        if (!sidebar) { return; }
        var KEY = 'davixH2iSidebar';
        try { if (window.localStorage.getItem(KEY) === '1') { sidebar.classList.add('is-collapsed'); } } catch (e) { /* storage may be blocked */ }
        var toggle = sidebar.querySelector('.davix-admin-sidebar-toggle');
        if (toggle) {
            toggle.addEventListener('click', function () {
                var collapsed = sidebar.classList.toggle('is-collapsed');
                try { window.localStorage.setItem(KEY, collapsed ? '1' : '0'); } catch (e) { /* ignore */ }
            });
        }
    });
})();

(function () {
    document.addEventListener('change', function (e) {
        var s = e.target;
        if (s && s.hasAttribute && s.hasAttribute('data-davix-h2i-picker') && s.value) { window.location.href = s.value; }
    });
})();
