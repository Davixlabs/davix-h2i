/* Help tab: copy system info, show / clear the debug log. */
(function () {
    'use strict';
    var esc = Dvx.esc;
    Dvx.ready(function () {
        var copy = Dvx.qs('[data-hp-copy]');
        if (copy) {
            copy.addEventListener('click', function () {
                var ta = Dvx.qs('[data-hp-sysinfo]');
                ta.select();
                try { document.execCommand('copy'); Dvx.toast('Copied.', 'success'); } catch (e) { Dvx.toast('Press Ctrl+C to copy.', 'info'); }
            });
        }
        var box = Dvx.qs('[data-hp-log]');
        if (box) {
            var load = function () {
                Dvx.api('logs').then(function (res) {
                    if (!res.lines.length) { box.innerHTML = '<div class="dvs-log-empty">' + (res.enabled ? 'The log is empty.' : 'Logging is off. Switch it on above and save.') + '</div>'; return; }
                    box.innerHTML = res.lines.map(function (l) {
                        var level = /\] (ERROR|INFO|DEBUG) /.exec(l);
                        return '<div class="dvs-log-row"><span class="dvs-log-row__message">' + esc(l) + '</span></div>';
                    }).join('');
                    box.scrollTop = box.scrollHeight;
                });
            };
            Dvx.qs('[data-hp-refresh]').addEventListener('click', load);
            Dvx.qs('[data-hp-clear]').addEventListener('click', function () { Dvx.api('logs/clear', { method: 'POST' }).then(load); });
            load();
        }
    });
})();
