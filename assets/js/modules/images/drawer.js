/* The Image tab of the Media Library drawer: runs the chosen chips on the chosen images. */
(function () {
    'use strict';
    if (!window.DvxDrawer) { return; }

    window.DvxDrawer.registerTab('image', {
        run: function (ctx) {
            var data = Dvx.formData(ctx.panel).drawer || {};
            var chips = (data.chips || []).filter(Boolean);
            if (!chips.length) { Dvx.toast('Add at least one chip first.', 'info'); return Promise.resolve(); }
            return Dvx.api('images/run-chips', { method: 'POST', body: { ids: ctx.ids, chips: chips, fields: data.fields || {}, mode: data.mode || '' } })
                .then(function (res) {
                    if (res.queued) {
                        Dvx.renderResult(ctx.box, res.queued + ' files', { ok: true, queued: true }, null);
                        if (window.DvxJobs) { window.DvxJobs.watch(ctx.box, res.batch_id, res.queued); }
                        return;
                    }
                    (res.results || []).forEach(function (o) { Dvx.renderResult(ctx.box, o.title || ('#' + o.object_id), o, null); });
                })
                .catch(function (err) { Dvx.renderResult(ctx.box, 'Error', err.data && err.data.ok === false ? err.data : null, err); });
        }
    });
})();
