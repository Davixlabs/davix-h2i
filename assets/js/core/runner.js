/* The generic run panel ([data-dvx-run]): choose files, choose an action, set options, run now or in the background.
   Also exposes Dvx.runOperation() for the Media Library drawer and the editor panels. */
(function () {
    'use strict';

    var esc = Dvx.esc;

    function typed(el) {
        var type = el.getAttribute('data-ptype');
        var v;
        if (type === 'switch') { v = el.checked; }
        else { v = el.value; }
        if (v === '' || v === null) { return undefined; }
        if (type === 'number') { var n = Number(v); return isNaN(n) ? undefined : n; }
        if (type === 'json') { try { return JSON.parse(v); } catch (e) { throw new Error('"' + el.getAttribute('data-p') + '" is not valid JSON.'); } }
        return v;
    }

    /** An option in "More options" is only sent when the person changed it from the service's default. */
    function isDefault(el, v) {
        var d = el.getAttribute('data-pdefault');
        var type = el.getAttribute('data-ptype');
        if (type === 'switch') { return String(v) === (d === 'true' ? 'true' : 'false'); }
        if (d === null || d === '') { return false; }
        if (type === 'number') { return Number(d) === v; }
        if (type === 'json') { try { return JSON.stringify(JSON.parse(d)) === JSON.stringify(v); } catch (e) { return false; } }
        return String(v) === d;
    }

    function collectParams(form) {
        var params = {};
        Dvx.qsa('[data-p]', form).forEach(function (el) {
            var type = el.getAttribute('data-ptype');
            if (type === 'switch' && el.type === 'hidden') { return; }
            if (el.type === 'hidden') { return; }
            var v = typed(el);
            if (v === undefined) { return; }
            if (el.hasAttribute('data-pdefault') && isDefault(el, v)) { return; }
            var name = el.getAttribute('data-p');
            var key = el.getAttribute('data-pkey');
            if (key !== null) {
                // A property of a group (the watermark's text, position, opacity ...): put the group back together.
                if (!params[name] || typeof params[name] !== 'object') { params[name] = {}; }
                params[name][key] = v;
            } else {
                params[name] = v;
            }
        });
        return params;
    }

    /** Runs one operation on the server and resolves with the outcome. */
    function runOperation(req) {
        return Dvx.api('run', { method: 'POST', body: req });
    }

    function renderResult(box, title, outcome, err) {
        var row = document.createElement('div');
        var failed = err || !outcome || !outcome.ok;
        row.className = 'dvx-result' + (failed ? ' is-error' : '');
        var html = '';
        if (!failed && outcome.outputs && outcome.outputs[0] && outcome.outputs[0].thumb) {
            html += '<img src="' + esc(outcome.outputs[0].thumb) + '" alt="" />';
        }
        html += '<div class="dvx-result__body"><strong>' + esc(title) + '</strong><div>';
        if (failed) {
            html += esc((err && err.message) || (outcome && outcome.message) || Dvx.i18n.failed);
            if (outcome && outcome.error && outcome.error.hint) { html += '<br><span class="dvx-muted">' + esc(outcome.error.hint) + '</span>'; }
        } else if (outcome.queued) {
            html += esc('Queued. It will run in the background.');
        } else {
            html += esc(outcome.message || Dvx.i18n.done);
            if (outcome.status === 'pending_review') { html += ' <span class="dvs-badge dvs-badge--warning">Needs review</span>'; }
            if (outcome.bytes_before && outcome.bytes_after && outcome.bytes_after < outcome.bytes_before) {
                html += ' <span class="dvs-badge dvs-badge--success">-' + Math.round(100 - 100 * outcome.bytes_after / outcome.bytes_before) + '%</span>';
            }
        }
        html += '</div>';
        if (!failed && outcome.text) {
            var lines = [];
            Object.keys(outcome.text).forEach(function (k) {
                var v = outcome.text[k];
                if (v === '' || v === null || (Array.isArray(v) && !v.length)) { return; }
                if (typeof v === 'object' && !Array.isArray(v)) { v = JSON.stringify(v); }
                lines.push(k.replace(/_/g, ' ') + ': ' + (Array.isArray(v) ? v.join(', ') : v));
            });
            if (lines.length) { html += '<div class="dvx-result__text">' + esc(lines.join('\n')) + '</div>'; }
        }
        if (!failed && outcome.data && Object.keys(outcome.data).length) {
            html += '<details><summary>Details</summary><pre class="dvx-result__text">' + esc(JSON.stringify(outcome.data, null, 2)) + '</pre></details>';
        }
        if (!failed && outcome.outputs && outcome.outputs.length) {
            html += '<div class="dvx-muted">';
            outcome.outputs.forEach(function (o, i) {
                if (o.edit_url) { html += (i ? ' · ' : '') + '<a href="' + esc(o.edit_url) + '" target="_blank" rel="noopener">' + esc(o.filename || ('#' + o.attachment_id)) + '</a>'; }
            });
            html += '</div>';
        }
        html += '</div>';
        row.innerHTML = html;
        box.insertBefore(row, box.firstChild);
    }

    function init(root) {
        var picked = [];       // [{id,title,thumb,mime}]
        var scopeIds = [];
        var extra = {};        // field -> {id,title}
        var current = '';
        var frame;

        var pickedBox = Dvx.qs('[data-dvx-picked]', root);
        var note = Dvx.qs('[data-dvx-picked-note]', root);
        var go = Dvx.qs('[data-dvx-go]', root);
        var results = Dvx.qs('[data-dvx-results]', root);
        var estimate = Dvx.qs('[data-dvx-estimate]', root);
        var source = root.getAttribute('data-source') || 'tab';

        function form() { return current ? Dvx.qs('[data-op-form="' + current + '"]', root) : null; }
        function needsFile(f) { return f && f.getAttribute('data-input') === 'file'; }
        function ids() { return scopeIds.length ? scopeIds.slice() : picked.map(function (p) { return p.id; }); }

        function refresh() {
            var f = form();
            Dvx.qsa('[data-op-form]', root).forEach(function (x) { x.classList.toggle('dvx-hidden', x !== f); });
            Dvx.qsa('[data-dvx-needs-op]', root).forEach(function (n) { n.hidden = !f; });
            var none = Dvx.qs('[data-dvx-no-op]', root);
            if (none) { none.classList.toggle('dvx-hidden', !!f); }
            var mode = Dvx.qs('[data-dvx-mode]', root);
            if (mode) {
                var store = f ? f.getAttribute('data-store') : '';
                mode.classList.toggle('dvx-hidden', !(store === 'replace' || store === 'copy'));
            }
            Dvx.qsa('[data-op-pick]', root).forEach(function (b) { b.classList.toggle('is-selected', b.getAttribute('data-op-pick') === current); });

            var n = ids().length;
            var ok = !!f && (window.DvxGate ? window.DvxGate.allowed(current) : true);
            if (f && needsFile(f) && n < 1) { ok = false; }
            if (f && f.getAttribute('data-batch') === 'pair' && n !== 1) { ok = false; }
            go.disabled = !ok;

            if (note) {
                note.textContent = n ? (scopeIds.length ? n + ' files chosen by rule.' : n + ' file' + (n > 1 ? 's' : '') + ' chosen.') : '';
            }
            Dvx.qsa('[data-op-cost]', root).forEach(function (el) {
                var info = window.DvxGate ? window.DvxGate.info(el.getAttribute('data-op-cost')) : null;
                el.textContent = info ? 'Cost: ' + info.cost + ' ' + (Dvx.i18n.units || 'units') + ' per call' : '';
            });
            if (f && ok) {
                var info = window.DvxGate.info(current);
                var count = (f.getAttribute('data-input') === 'none' || f.getAttribute('data-batch') === 'all') ? 1 : Math.max(1, n);
                var cost = info ? info.cost * count : 0;
                estimate.classList.remove('dvx-hidden');
                estimate.innerHTML = '<p>About <strong>' + cost + '</strong> ' + esc(Dvx.i18n.units || 'units') + ' will count against your plan.</p>';
            } else {
                estimate.classList.add('dvx-hidden');
            }
        }

        function drawPicked() {
            pickedBox.innerHTML = '';
            picked.forEach(function (p, i) {
                var d = document.createElement('div');
                d.className = 'dvx-picked__item';
                d.innerHTML = (p.thumb ? '<img src="' + esc(p.thumb) + '" alt="" />' : '<span class="dvx-picked__file">' + esc((p.mime || '').split('/')[1] || 'file') + '</span>') +
                    '<button type="button" class="dvx-picked__remove" data-i="' + i + '" aria-label="Remove">&times;</button>';
                pickedBox.appendChild(d);
            });
        }

        function openPicker(multiple, cb) {
            if (!window.wp || !wp.media) { Dvx.toast('The Media Library is not available here.', 'error'); return; }
            var f = wp.media({ title: Dvx.i18n.chooseFiles, multiple: multiple, library: {} });
            f.on('select', function () {
                var sel = f.state().get('selection').toJSON();
                cb(sel);
            });
            f.open();
        }

        Dvx.on(root, 'click', '[data-dvx-pick]', function () {
            openPicker(true, function (sel) {
                scopeIds = [];
                sel.forEach(function (a) {
                    if (picked.some(function (p) { return p.id === a.id; })) { return; }
                    var thumb = a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : (a.type === 'image' ? a.url : '');
                    picked.push({ id: a.id, title: a.title, thumb: thumb, mime: a.mime });
                });
                drawPicked(); refresh();
            });
        });

        Dvx.on(root, 'click', '[data-dvx-pick-extra]', function (e, btn) {
            var field = btn.getAttribute('data-dvx-pick-extra');
            openPicker(false, function (sel) {
                if (!sel[0]) { return; }
                extra[field] = { id: sel[0].id, title: sel[0].title };
                var label = Dvx.qs('[data-extra-label="' + field + '"]', root);
                if (label) { label.textContent = sel[0].title; }
            });
        });

        Dvx.on(root, 'click', '.dvx-picked__remove', function (e, btn) {
            picked.splice(Number(btn.getAttribute('data-i')), 1);
            drawPicked(); refresh();
        });

        var opSelect = Dvx.qs('[data-op-select]', root);
        if (opSelect) {
            opSelect.addEventListener('change', function () {
                var b = opSelect.value ? Dvx.qs('[data-op-pick="' + opSelect.value + '"]', root) : null;
                var d = Dvx.qs('[data-op-desc]', root);
                if (d) { d.textContent = b ? b.getAttribute('data-op-desc-text') || '' : ''; }
                if (b && b.classList.contains('is-locked')) { Dvx.toast('This action is not in your plan.', 'error'); opSelect.value = current || ''; return; }
                if (b) { b.click(); } else { current = ''; refresh(); }
            });
        }
        Dvx.on(root, 'click', '[data-op-pick]', function (e, btn) {
            if (btn.classList.contains('is-locked')) { return; }
            current = btn.getAttribute('data-op-pick');
            refresh();
        });

        var scopeSel = Dvx.qs('[data-dvx-scope]', root);
        if (scopeSel) {
            scopeSel.addEventListener('change', function () {
                if (!scopeSel.value) { scopeIds = []; refresh(); return; }
                if (!current) { Dvx.toast('Choose an action first.', 'info'); scopeSel.value = ''; return; }
                Dvx.api('bulk/scope', { query: { operation: current, filter: scopeSel.value === 'all' ? '' : scopeSel.value } })
                    .then(function (res) { picked = []; drawPicked(); scopeIds = res.ids || []; refresh(); })
                    .catch(function (err) { Dvx.toast(err.message, 'error'); });
            });
        }

        function modeValue() { var m = Dvx.qs('[data-dvx-mode-select]', root); return m ? m.value : ''; }

        go.addEventListener('click', function () {
            var f = form();
            if (!f) { return; }
            var params;
            try { params = collectParams(f); } catch (e) { Dvx.toast(e.message, 'error'); return; }
            var list = ids();
            var queueEl = Dvx.qs('[data-dvx-queue]', root);
            var queue = queueEl && queueEl.checked;
            var batch = f.getAttribute('data-batch') || 'each';
            var mode = modeValue();
            var extraIds = {};
            Object.keys(extra).forEach(function (k) { extraIds[k] = extra[k].id; });
            var title = (Dvx.qs('[data-op-pick="' + current + '"] .dvx-op__title span', root) || {}).textContent || current;

            go.disabled = true;
            go.textContent = Dvx.i18n.working;

            var finish = function () { go.textContent = 'Run'; refresh(); };

            if (list.length > 1 && batch === 'each' && (queue || list.length > 5) && root.getAttribute('data-bulk') === '1') {
                Dvx.api('bulk', { method: 'POST', body: { operation: current, ids: list, params: params, mode: mode } })
                    .then(function (res) {
                        renderResult(results, title, { ok: true, queued: true, message: res.queued + ' queued' }, null);
                        if (window.DvxJobs) { window.DvxJobs.watch(results, res.batch_id, res.queued); }
                    })
                    .catch(function (err) { renderResult(results, title, null, err); })
                    .then(finish);
                return;
            }

            var calls = [];
            if (f.getAttribute('data-input') === 'none' || list.length === 0) {
                calls.push({ id: 0, title: title });
            } else if (batch === 'all') {
                calls.push({ ids: list, title: title });
            } else if (batch === 'pair') {
                calls.push({ id: list[0], title: title });
            } else {
                list.forEach(function (id) {
                    var p = picked.filter(function (x) { return x.id === id; })[0];
                    calls.push({ id: id, title: p ? p.title : ('#' + id) });
                });
            }

            var chain = Promise.resolve();
            calls.forEach(function (c) {
                chain = chain.then(function () {
                    var body = { operation: current, object_id: c.id || 0, object_ids: c.ids || [], params: params, mode: mode, source: source, extra: extraIds, queue: queue && calls.length === 1 && f.getAttribute('data-input') !== 'none' };
                    return runOperation(body)
                        .then(function (out) { renderResult(results, c.title, out, null); })
                        .catch(function (err) { renderResult(results, c.title, err.data && err.data.ok === false ? err.data : null, err); });
                });
            });
            chain.then(finish, finish);
        });

        var preselected = Dvx.qs('[data-op-pick].is-selected', root);
        if (preselected) { current = preselected.getAttribute('data-op-pick'); }
        refresh();
    }

    Dvx.ready(function () {
        Dvx.qsa('[data-dvx-run]').forEach(init);
    });

    Dvx.runOperation = runOperation;
    Dvx.renderResult = renderResult;
    Dvx.collectParams = collectParams;
})();
