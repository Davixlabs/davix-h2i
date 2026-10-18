/* The Media Library sidebar. It opens on one or more files ([data-dvx-open-drawer], or the bulk action "Open in Davix H2I") and
   shows one tab per kind of work that fits the files: Image (the chips), Analyze, AI, PDF, Document, Render ...; "History" for a
   single file. A tab of kind "ops" lists the actions of its module that fit every chosen file, and shows the form of the chosen one. */
(function () {
    'use strict';
    var esc = Dvx.esc;
    var handlers = {};     // tab id -> { run(ctx) }

    var drawer, backdrop, tabsBox, panel, results, runBtn, costEl;
    var ids = [], infos = [], activeTab = null, actionOp = '', extraSel = {};

    function mimeOk(mimes) {
        if (!mimes.length) { return true; }
        return infos.length > 0 && infos.every(function (i) { return mimes.some(function (m) { return i.mime.indexOf(m) === 0; }); });
    }

    function opsForAll() {
        if (!infos.length) { return []; }
        var first = infos[0].ops;
        return first.filter(function (op) { return infos.every(function (i) { return i.ops.indexOf(op) > -1; }); });
    }

    function label(op) { return (Dvx.config.opLabels || {})[op] || op; }
    function allowed(op) { return window.DvxGate ? window.DvxGate.allowed(op) : true; }

    function buildTabs() {
        var tabs = [];
        var fits = opsForAll();
        Dvx.qsa('template[data-dvx-drawer-template]', drawer).forEach(function (t) {
            var mimes = (t.getAttribute('data-mimes') || '').split(',').filter(Boolean);
            var tab = { id: t.getAttribute('data-dvx-drawer-template'), label: t.getAttribute('data-label'), tpl: t };
            if (t.getAttribute('data-kind') === 'ops') {
                var any = t.getAttribute('data-any') === '1';
                var list = (t.getAttribute('data-ops') || '').split(',').filter(Boolean).filter(function (op) { return (any || fits.indexOf(op) > -1) && allowed(op); });
                if (!list.length || !mimeOk(mimes)) { return; }
                tab.ops = list; tab.tpl = null;
            } else if (!mimeOk(mimes)) {
                return;
            }
            tabs.push(tab);
        });
        if (ids.length === 1) { tabs.push({ id: 'history', label: 'History' }); }
        return tabs;
    }

    function selectTab(tab) {
        activeTab = tab;
        extraSel = {};
        actionOp = '';
        Dvx.qsa('.dvs-drawer-tab', tabsBox).forEach(function (b) { b.classList.toggle('is-active', b.getAttribute('data-tab') === tab.id); });
        panel.innerHTML = '';
        runBtn.disabled = false;
        costEl.textContent = '';
        if (tab.ops) {
            mountOps(tab.ops);
        } else if (tab.tpl) {
            panel.appendChild(tab.tpl.content.cloneNode(true));
            if (window.DvxChips) { Dvx.qsa('[data-chip-row]', panel).forEach(function (r) { window.DvxChips.sync(r.getAttribute('data-chip-row')); }); }
        } else if (tab.id === 'history') {
            runBtn.disabled = true;
            mountHistory();
        }
    }

    function loadForm(op, box) {
        actionOp = op;
        extraSel = {};
        box.innerHTML = '';
        runBtn.disabled = !op;
        if (!op) { costEl.textContent = ''; return; }
        var info = window.DvxGate && window.DvxGate.info(op);
        box.innerHTML = '<div class="dvs-empty-state"><p>Loading…</p></div>';
        Dvx.api('drawer/form', { query: { operation: op } }).then(function (res) {
            box.innerHTML = res.html;
            var f = box.querySelector('[data-op-form]');
            if (f) { f.classList.remove('dvx-hidden'); }
            var each = !f || (f.getAttribute('data-batch') || 'each') === 'each';
            costEl.textContent = info ? 'Cost: ' + (info.cost * (each ? ids.length : 1)) + ' ' + (Dvx.i18n.units || 'units') : '';
        }).catch(function (err) { box.innerHTML = '<p class="dvx-muted">' + esc(err.message) + '</p>'; });
    }

    /** The actions of one tab: a choice of action (when there are several) and the form of the chosen one. */
    function mountOps(ops) {
        if (!ops.length) { panel.innerHTML = '<div class="dvs-empty-state"><p>No action fits every chosen file.</p></div>'; runBtn.disabled = true; return; }
        if (ops.length === 1) {
            panel.innerHTML = '<div data-dvx-drawer-opform></div>';
            loadForm(ops[0], Dvx.qs('[data-dvx-drawer-opform]', panel));
            return;
        }
        panel.innerHTML = '<div class="davix-h2i-field-group"><label class="davix-h2i-label" for="dvx-drawer-op">Action</label><select id="dvx-drawer-op" class="davix-h2i-select"><option value="">Choose an action…</option>' +
            ops.map(function (op) { return '<option value="' + esc(op) + '">' + esc(label(op)) + '</option>'; }).join('') + '</select></div><div data-dvx-drawer-opform></div>';
        runBtn.disabled = true;
        var sel = Dvx.qs('select', panel);
        sel.addEventListener('change', function () { loadForm(sel.value, Dvx.qs('[data-dvx-drawer-opform]', panel)); });
    }

    function mountHistory() {
        panel.innerHTML = '<div class="dvs-empty-state"><p>Loading…</p></div>';
        Dvx.api('history', { query: { object_id: ids[0], per_page: 10 } }).then(function (res) {
            if (!res.items.length) { panel.innerHTML = '<div class="dvs-empty-state"><p>Nothing has been done to this file yet.</p></div>'; return; }
            var rows = res.items.map(function (r) {
                var tone = { success: 'success', failed: 'danger', pending_review: 'warning', restored: 'info' }[r.status] || 'info';
                return '<tr><td>' + esc(r.label) + '</td><td><span class="dvs-badge dvs-badge--' + tone + '">' + esc(r.status.replace('_', ' ')) + '</span></td><td class="dvx-muted">' + esc(r.ago) + ' ago</td><td>' +
                    (r.can_restore ? '<button class="dvs-btn" data-restore="' + r.id + '">Restore original</button>' : '') + '</td></tr>';
            }).join('');
            panel.innerHTML = '<div class="dvx-table-wrap"><table class="dvx-table"><tbody>' + rows + '</tbody></table></div>';
        });
        panel.onclick = function (e) {
            var b = e.target.closest('[data-restore]');
            if (!b || !window.confirm(Dvx.i18n.confirm)) { return; }
            Dvx.api('history/' + b.getAttribute('data-restore') + '/restore', { method: 'POST' }).then(function (r) { Dvx.toast(r.message || Dvx.i18n.done, 'success'); mountHistory(); }).catch(function (err) { Dvx.toast(err.message, 'error'); });
        };
    }

    function title(id) { return (infos.filter(function (i) { return i.id === id; })[0] || {}).title || ('#' + id); }

    function open(list) {
        ids = list.map(Number).filter(Boolean);
        if (!ids.length) { return; }
        results.innerHTML = '';
        panel.innerHTML = '<div class="dvs-empty-state"><p>Loading…</p></div>';
        drawer.hidden = false; backdrop.hidden = false;
        requestAnimationFrame(function () { drawer.classList.add('is-open'); backdrop.classList.add('is-open'); });
        document.body.classList.add('dvs-drawer-open');
        Dvx.api('media/info', { query: { ids: ids.join(',') } }).then(function (res) {
            infos = res.items;
            ids = infos.map(function (i) { return i.id; });
            var tabs = buildTabs();
            if (!tabs.length) { panel.innerHTML = '<div class="dvs-empty-state"><p>Nothing here fits these files.</p></div>'; runBtn.disabled = true; tabsBox.innerHTML = ''; return; }
            tabsBox.innerHTML = tabs.map(function (t) { return '<button type="button" class="dvs-drawer-tab davix-h2i-tab-btn" data-tab="' + esc(t.id) + '">' + esc(t.label) + '</button>'; }).join('');
            tabsBox.onclick = function (e) {
                var b = e.target.closest('[data-tab]');
                if (!b) { return; }
                var t = tabs.filter(function (x) { return x.id === b.getAttribute('data-tab'); })[0];
                if (t) { selectTab(t); }
            };
            selectTab(tabs[0]);
        }).catch(function (err) { panel.innerHTML = '<div class="dvs-empty-state"><p>' + esc(err.message) + '</p></div>'; });
    }

    function close() {
        drawer.classList.remove('is-open'); backdrop.classList.remove('is-open');
        document.body.classList.remove('dvs-drawer-open');
        setTimeout(function () { drawer.hidden = true; backdrop.hidden = true; }, 280);
    }

    /** Runs the open action on the chosen files: one call per file, one call for all of them, or one call for a pair. */
    function runAction(done) {
        var f = Dvx.qs('[data-op-form]', panel);
        var params;
        try { params = Dvx.collectParams(f); } catch (e) { Dvx.toast(e.message, 'error'); done(); return; }
        var mode = (Dvx.qs('[data-dvx-drawer-mode]', panel) || {}).value || '';
        var batch = f.getAttribute('data-batch') || 'each';
        var finish = function (name, out, err) { Dvx.renderResult(results, name, out, err); };
        var fail = function (name) { return function (err) { finish(name, err.data && err.data.ok === false ? err.data : null, err); }; };
        var base = { operation: actionOp, params: params, mode: mode, source: 'drawer' };
        if (batch === 'all') {
            Dvx.runOperation(Object.assign({}, base, { object_id: ids[0], object_ids: ids, extra: extraSel }))
                .then(function (out) { finish(ids.length + ' files', out, null); }).catch(fail(ids.length + ' files')).then(done, done);
            return;
        }
        if (batch === 'pair') {
            var extra = Object.assign({}, extraSel);
            var field = (Dvx.qs('[data-dvx-pick-extra]', f) || {}).getAttribute ? Dvx.qs('[data-dvx-pick-extra]', f).getAttribute('data-dvx-pick-extra') : '';
            if (ids.length === 2 && field) { extra[field] = ids[1]; }
            else if (ids.length > 2) { Dvx.toast('Choose two files to compare.', 'error'); done(); return; }
            else if (!extra[field]) { Dvx.toast('Choose the second file first.', 'error'); done(); return; }
            Dvx.runOperation(Object.assign({}, base, { object_id: ids[0], extra: extra }))
                .then(function (out) { finish(title(ids[0]), out, null); }).catch(fail(title(ids[0]))).then(done, done);
            return;
        }
        var chain = Promise.resolve();
        ids.forEach(function (id) {
            chain = chain.then(function () {
                return Dvx.runOperation(Object.assign({}, base, { object_id: id, extra: extraSel }))
                    .then(function (out) { finish(title(id), out, null); })
                    .catch(fail(title(id)));
            });
        });
        chain.then(done, done);
    }

    function run() {
        var ctx = { ids: ids.slice(), infos: infos, box: results, source: 'drawer', panel: panel };
        runBtn.disabled = true; runBtn.textContent = Dvx.i18n.working;
        var done = function () { runBtn.disabled = false; runBtn.textContent = 'Run'; };
        if (activeTab && activeTab.ops) {
            if (!actionOp) { Dvx.toast('Choose an action first.', 'info'); done(); return; }
            runAction(done);
            return;
        }
        var h = activeTab ? handlers[activeTab.id] : null;
        if (h && h.run) {
            Promise.resolve(h.run(ctx)).then(done, done);
        } else { done(); }
    }

    Dvx.ready(function () {
        drawer = document.getElementById('davix-h2i-modal');
        backdrop = document.getElementById('dvx-drawer-backdrop');
        if (!drawer || !backdrop) { return; }
        tabsBox = Dvx.qs('[data-dvx-drawer-tabs]', drawer);
        panel = Dvx.qs('[data-dvx-drawer-panel]', drawer);
        results = Dvx.qs('[data-dvx-drawer-results]', drawer);
        runBtn = Dvx.qs('[data-dvx-drawer-run]', drawer);
        costEl = Dvx.qs('[data-dvx-drawer-cost]', drawer);
        runBtn.addEventListener('click', run);
        // Extra files (a logo, a mask, a second image or PDF) are chosen from the Media Library.
        drawer.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-dvx-pick-extra]');
            if (!btn) { return; }
            if (!window.wp || !wp.media) { Dvx.toast('The Media Library is not available here.', 'error'); return; }
            var field = btn.getAttribute('data-dvx-pick-extra');
            var frame = wp.media({ title: Dvx.i18n.chooseFiles, multiple: false, library: {} });
            frame.on('select', function () {
                var a = frame.state().get('selection').first().toJSON();
                extraSel[field] = a.id;
                var lab = Dvx.qs('[data-extra-label="' + field + '"]', drawer);
                if (lab) { lab.textContent = a.title || a.filename; }
            });
            frame.open();
        });
        backdrop.addEventListener('click', close);
        Dvx.qs('[data-dvx-drawer-close]', drawer).addEventListener('click', close);
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !drawer.hidden) { close(); } });
        document.addEventListener('click', function (e) {
            var t = e.target.closest('[data-dvx-open-drawer]');
            if (!t) { return; }
            e.preventDefault();
            open([t.getAttribute('data-dvx-open-drawer')]);
        });
        // The bulk action "Open in Davix H2I" on the Media Library list: open the sidebar on every ticked file.
        var list = document.getElementById('posts-filter');
        if (list) {
            list.addEventListener('submit', function (e) {
                var a = (list.querySelector('select[name="action"]') || {}).value;
                var b = (list.querySelector('select[name="action2"]') || {}).value;
                if (a !== 'davix_h2i_open' && b !== 'davix_h2i_open') { return; }
                e.preventDefault();
                var picked = Dvx.qsa('input[name="media[]"]:checked', list).map(function (c) { return c.value; });
                if (!picked.length) { Dvx.toast('Tick the files you want to work on first.', 'info'); return; }
                open(picked);
            });
        }
    });

    window.DvxDrawer = {
        open: open,
        close: close,
        registerTab: function (id, handler) { handlers[id] = handler; }
    };
})();
