/* Watches a bulk batch: shows a progress bar and the counts, polls the queue, and offers Pause, Retry and Run now. */
(function () {
    'use strict';

    function watch(container, batchId, total) {
        var box = document.createElement('div');
        box.className = 'dvx-result';
        box.innerHTML = '<div class="dvx-result__body"><strong>Bulk run</strong><div class="dvx-bar"><span style="width:0%"></span></div><div class="dvx-muted" data-line></div>' +
            '<div class="dvx-toolbar" style="margin:8px 0 0"><button type="button" class="dvs-btn" data-act="run">Run now</button><button type="button" class="dvs-btn" data-act="retry">Retry failed</button><button type="button" class="dvs-btn dvs-btn--danger" data-act="cancel">Cancel</button></div></div>';
        container.insertBefore(box, container.firstChild);
        var bar = box.querySelector('.dvx-bar span');
        var line = box.querySelector('[data-line]');
        var stopped = false;

        function tick() {
            Dvx.api('jobs/summary', { query: { batch_id: batchId } }).then(function (res) {
                var c = res.counts;
                var done = c.done + c.failed + c.cancelled;
                var pct = total ? Math.round(100 * done / Math.max(total, c.total)) : 0;
                bar.style.width = pct + '%';
                var parts = [c.done + ' done'];
                if (c.running || c.waiting_remote) { parts.push((c.running + c.waiting_remote) + ' running'); }
                if (c.queued) { parts.push(c.queued + ' waiting'); }
                if (c.failed) { parts.push(c.failed + ' failed'); }
                if (res.paused) { parts.push(res.paused === 'quota' ? 'paused: your allowance is used up' : 'paused: check your API key'); }
                line.textContent = parts.join(' · ');
                if (c.queued + c.running + c.waiting_remote === 0 || res.paused) { stopped = true; }
                if (!stopped) { setTimeout(tick, 4000); }
            }).catch(function () { if (!stopped) { setTimeout(tick, 8000); } });
        }

        box.addEventListener('click', function (e) {
            var b = e.target.closest('[data-act]');
            if (!b) { return; }
            var act = b.getAttribute('data-act');
            if (act === 'run') { Dvx.api('jobs/run-now', { method: 'POST' }).then(function () { stopped = false; tick(); }); }
            if (act === 'retry') { Dvx.api('jobs/retry', { method: 'POST', body: { batch_id: batchId } }).then(function () { stopped = false; tick(); }); }
            if (act === 'cancel') { Dvx.api('jobs/cancel', { method: 'POST', body: { batch_id: batchId } }).then(function () { tick(); }); }
        });

        // Start the first slice right away so the user sees progress without waiting for cron.
        Dvx.api('jobs/run-now', { method: 'POST' }).then(tick, tick);
    }

    window.DvxJobs = { watch: watch };
})();
