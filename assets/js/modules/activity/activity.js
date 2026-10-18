/* Activity tab: live job list with Run now / Retry / Cancel, and the Review actions. */
(function () {
    'use strict';
    var esc = Dvx.esc;

    Dvx.ready(function () {
        var root = Dvx.qs('[data-ac-root]');
        if (root) {
            var body = Dvx.qs('[data-ac-body]', root);
            var statusSel = Dvx.qs('[data-ac-status]', root);
            var stats = Dvx.qsa('.dvx-stat__value', root);
            var timer;

            var load = function () {
                Dvx.api('jobs', { query: { status: statusSel.value, per_page: 50 } }).then(function (res) {
                    if (!res.items.length) {
                        body.innerHTML = '<tr><td colspan="6"><div class="dvs-empty-state"><p>No jobs.</p></div></td></tr>';
                    } else {
                        body.innerHTML = res.items.map(function (j) {
                            var tone = { done: 'success', failed: 'danger', cancelled: 'warning' }[j.status] || 'info';
                            var label = { queued: 'Waiting', running: 'Running', waiting_remote: 'In the cloud', done: 'Done', failed: 'Failed', cancelled: 'Cancelled' }[j.status] || j.status;
                            return '<tr><td>' + esc(j.title || (j.object_id ? '#' + j.object_id : '-')) + '</td><td>' + esc(j.operation) + '</td><td><span class="dvs-badge dvs-badge--' + tone + '">' + esc(label) + '</span>' +
                                (j.error_message ? '<div class="dvx-muted">' + esc(j.error_message) + '</div>' : '') + '</td><td>' + esc(j.attempts) + '</td><td class="dvx-muted">' + esc(j.updated_at) + '</td><td>' +
                                (j.status === 'queued' || j.status === 'waiting_remote' ? '<button class="dvs-btn" data-cancel="' + j.id + '">Cancel</button>' : '') + '</td></tr>';
                        }).join('');
                    }
                });
                Dvx.api('jobs/summary').then(function (res) {
                    var c = res.counts;
                    if (stats.length >= 4) { stats[0].textContent = c.queued; stats[1].textContent = c.running + c.waiting_remote; stats[2].textContent = c.done; stats[3].textContent = c.failed; }
                    var paused = Dvx.qs('[data-ac-paused]', root);
                    if (paused) { paused.classList.toggle('dvx-hidden', !res.paused); }
                });
                timer = setTimeout(load, 6000);
            };
            statusSel.addEventListener('change', function () { clearTimeout(timer); load(); });
            Dvx.on(document, 'click', '[data-cancel]', function (e, b) { Dvx.api('jobs/cancel', { method: 'POST', body: { id: Number(b.getAttribute('data-cancel')) } }).then(function () { clearTimeout(timer); load(); }); });
            var act = function (sel, fn) { var el = Dvx.qs(sel); if (el) { el.addEventListener('click', fn); } };
            act('[data-ac-run]', function () { Dvx.api('jobs/run-now', { method: 'POST' }).then(function () { Dvx.toast('Ran the queue.', 'success'); clearTimeout(timer); load(); }); });
            act('[data-ac-retry]', function () { Dvx.api('jobs/retry', { method: 'POST', body: {} }).then(function (r) { Dvx.toast(r.retried + ' queued again.', 'success'); clearTimeout(timer); load(); }); });
            act('[data-ac-clear]', function () { Dvx.api('jobs/clear', { method: 'POST' }).then(function () { clearTimeout(timer); load(); }); });
            act('[data-ac-resume]', function () { Dvx.api('jobs/resume', { method: 'POST' }).then(function () { clearTimeout(timer); load(); }); });
            load();
        }

        var review = Dvx.qs('[data-ac-review]');
        if (review) {
            review.addEventListener('click', function (e) {
                var row = e.target.closest('[data-hid]');
                if (!row) { return; }
                var id = row.getAttribute('data-hid');
                var done = function () { row.remove(); Dvx.toast(Dvx.i18n.done, 'success'); };
                if (e.target.closest('[data-ac-approve]')) {
                    var text = {};
                    Dvx.qsa('[data-ac-field]', row).forEach(function (f) { text[f.getAttribute('data-ac-field')] = f.value; });
                    Dvx.api('history/' + id + '/approve', { method: 'POST', body: { text: text } }).then(done).catch(function (err) { Dvx.toast(err.message, 'error'); });
                } else if (e.target.closest('[data-ac-reject]')) {
                    Dvx.api('history/' + id + '/reject', { method: 'POST' }).then(done).catch(function (err) { Dvx.toast(err.message, 'error'); });
                }
            });
        }
    });
})();
