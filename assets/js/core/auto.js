/* The "Automatic" screen: rules that run on every new upload. A table of the rules (a row opens a side panel), "New rule" on top.
   A rule = what to do (an action and its settings), which file types, the smallest file, and whether the result replaces the file. */
(function () {
    'use strict';
    var esc = Dvx.esc;

    Dvx.ready(function () {
        var root = Dvx.qs('[data-auto-root]');
        if (!root) { return; }
        var allowed = JSON.parse(root.getAttribute('data-actions') || '[]');
        var single = root.hasAttribute('data-single'); // only one thing to do here: no choice to show
        var body = Dvx.qs('[data-auto-body]', root);
        var editor = Dvx.qs('[data-auto-editor]', root);
        var backdrop = Dvx.qs('[data-auto-backdrop]', root);
        // On <body>, so no sticky panel or header can sit above the side panel.
        document.body.appendChild(backdrop);
        document.body.appendChild(editor);
        var newBtn = Dvx.qs('[data-auto-new]');
        var titleEl = Dvx.qs('[data-auto-title]', editor);
        var actionSel = Dvx.qs('[data-auto-action]', editor);
        var actionBox = Dvx.qs('[data-auto-actionbox]', editor);
        var descEl = Dvx.qs('[data-auto-desc]', editor);
        var enabledEl = Dvx.qs('[data-auto-enabled]', editor);
        var typesBox = Dvx.qs('[data-auto-types]', editor);
        var typesWrap = Dvx.qs('[data-auto-typesbox]', editor);
        var minEl = Dvx.qs('[data-auto-min]', editor);
        var genEl = Dvx.qs('[data-auto-generated]', editor);
        var modeEl = Dvx.qs('[data-auto-mode]', editor);
        var modeWrap = Dvx.qs('[data-auto-modebox]', editor);
        var formBox = Dvx.qs('[data-auto-form]', editor);
        var saveBtn = Dvx.qs('[data-auto-save]', editor);
        var delBtn = Dvx.qs('[data-auto-delete]', editor);
        var state = { rules: [], actions: {} };
        var current = null;      // the rule being edited, or null for a new one
        var virtual = false;     // the open form belongs to a made-up action (the image chips)

        function mine(rule) { return allowed.indexOf(rule.action) > -1; }
        function actionOf(rule) { return state.actions[rule.action] || { label: rule.action, types: {} }; }

        // ------------------------------------------------------------ list
        function typesText(rule) {
            var a = actionOf(rule);
            if (!rule.types || !rule.types.length) { return 'All supported files'; }
            return rule.types.map(function (t) { return (a.types && a.types[t]) || t; }).join(', ');
        }

        function draw() {
            var rows = state.rules.map(function (r, i) { return { r: r, i: i }; }).filter(function (x) { return mine(x.r); });
            if (!rows.length) {
                body.innerHTML = '<tr><td colspan="3"><div class="dvs-empty-state"><span class="dashicons dashicons-update"></span><p>No automatic rules yet. Press "New rule" to add one.</p></div></td></tr>';
                return;
            }
            body.innerHTML = rows.map(function (x) {
                var a = actionOf(x.r);
                return '<tr class="dvx-row-click" tabindex="0" role="button" data-i="' + x.i + '"><td><strong>' + esc(a.label) + '</strong>' +
                    (a.description ? '<div class="dvx-muted">' + esc(a.description) + '</div>' : '') + '</td><td>' + esc(typesText(x.r)) + (x.r.min_kb ? '<div class="dvx-muted">Files of ' + esc(x.r.min_kb) + ' KB or more</div>' : '') + '</td>' +
                    '<td>' + (x.r.enabled ? '<span class="dvs-badge dvs-badge--success">On</span>' : '<span class="dvs-badge dvs-badge--info">Off</span>') + '</td></tr>';
            }).join('');
        }

        function load() {
            return Dvx.api('auto/rules').then(function (res) {
                state.rules = res.rules || [];
                state.actions = res.actions || {};
                actionSel.innerHTML = '<option value="">Choose what to do…</option>' + allowed.filter(function (id) { return state.actions[id]; }).map(function (id) {
                    var a = state.actions[id];
                    return '<option value="' + esc(id) + '"' + (a.locked ? ' disabled' : '') + '>' + esc(a.label) + (a.locked ? ' (not in your plan)' : '') + '</option>';
                }).join('');
                draw();
            }).catch(function (err) { body.innerHTML = '<tr><td colspan="3">' + esc(err.message) + '</td></tr>'; });
        }

        // ------------------------------------------------------------ editor
        function open() {
            editor.hidden = false; backdrop.hidden = false;
            requestAnimationFrame(function () { editor.classList.add('is-open'); backdrop.classList.add('is-open'); });
        }
        function close() {
            editor.classList.remove('is-open'); backdrop.classList.remove('is-open');
            setTimeout(function () { if (!editor.classList.contains('is-open')) { editor.hidden = true; backdrop.hidden = true; } }, 280);
        }

        function fillParams(params) {
            Dvx.qsa('[data-p]', formBox).forEach(function (el) {
                var key = el.getAttribute('data-p');
                var sub = el.getAttribute('data-pkey');
                var v;
                if (!params) { return; }
                if (sub !== null) { v = params[key] && typeof params[key] === 'object' ? params[key][sub] : undefined; } else { v = params[key]; }
                if (v === undefined) { return; }
                var type = el.getAttribute('data-ptype');
                if (type === 'switch') { el.checked = !!v; }
                else if (type === 'json') { el.value = typeof v === 'string' ? v : JSON.stringify(v); }
                else { el.value = v; }
            });
        }

        function showTypes(action, chosen) {
            var types = (action && action.types) || {};
            var keys = Object.keys(types);
            typesWrap.hidden = !keys.length;
            typesBox.innerHTML = keys.map(function (t) {
                return '<label class="davix-h2i-checkbox-item"><input type="checkbox" value="' + esc(t) + '"' + (chosen && chosen.indexOf(t) > -1 ? ' checked' : '') + ' /> ' + esc(types[t]) + '</label>';
            }).join('');
        }

        function loadForm(actionId, rule) {
            formBox.innerHTML = '';
            descEl.textContent = '';
            var action = state.actions[actionId];
            showTypes(action, rule ? rule.types : []);
            modeWrap.hidden = !(action && action.choose_mode);
            var onPublish = !!(action && action.trigger === 'publish');
            Dvx.qsa('[data-auto-uploadonly]', editor).forEach(function (n) { n.hidden = onPublish; });
            if (!actionId) { return Promise.resolve(); }
            descEl.textContent = action.description || '';
            formBox.innerHTML = '<div class="dvs-empty-state"><p>Loading…</p></div>';
            return Dvx.api('auto/form', { query: { action: actionId, rule: rule ? rule.id : '' } }).then(function (res) {
                virtual = !!res.virtual;
                formBox.innerHTML = res.html;
                Dvx.qsa('.dvx-hidden', formBox).forEach(function (n) { n.classList.remove('dvx-hidden'); });
                if (window.DvxChips) { Dvx.qsa('[data-chip-row]', formBox).forEach(function (r) { window.DvxChips.sync(r.getAttribute('data-chip-row')); }); }
                if (!virtual) { fillParams(rule ? rule.params : null); }
            }).catch(function (err) { formBox.innerHTML = '<p class="dvx-muted">' + esc(err.message) + '</p>'; });
        }

        function edit(rule) {
            current = rule;
            titleEl.textContent = rule ? actionOf(rule).label : (single && state.actions[allowed[0]] ? 'New rule: ' + state.actions[allowed[0]].label : 'New rule');
            actionSel.value = rule ? rule.action : '';
            actionSel.disabled = !!rule;
            actionBox.hidden = single;
            if (single && !rule) { actionSel.value = allowed[0]; }
            enabledEl.checked = rule ? !!rule.enabled : true;
            minEl.value = rule && rule.min_kb ? rule.min_kb : '';
            genEl.checked = rule ? !!rule.generated : false;
            modeEl.value = rule ? rule.mode : '';
            delBtn.hidden = !rule;
            loadForm(rule ? rule.action : (single ? allowed[0] : ''), rule);
            open();
        }

        function gather() {
            var id = actionSel.value;
            var params = {};
            if (virtual && Dvx.qs('[data-auto-plain]', formBox)) {
                Dvx.qsa('[data-vp]', formBox).forEach(function (el) {
                    var k = el.getAttribute('data-vp');
                    params[k] = el.getAttribute('data-vp-type') === 'switch' ? el.checked : el.value;
                });
            } else if (virtual) {
                var d = Dvx.formData(formBox).auto || {};
                params = { chips: (d.chips || []).filter(Boolean), fields: d.fields || {} };
            } else {
                var f = Dvx.qs('[data-op-form]', formBox);
                if (f) { params = Dvx.collectParams(f); }
            }
            return {
                id: current ? current.id : '',
                action: id,
                enabled: enabledEl.checked,
                types: Dvx.qsa('input[type=checkbox]', typesBox).filter(function (c) { return c.checked; }).map(function (c) { return c.value; }),
                min_kb: Number(minEl.value) || 0,
                generated: genEl.checked,
                mode: modeWrap.hidden ? '' : modeEl.value,
                params: params
            };
        }

        function persist(rules, message) {
            return Dvx.api('auto/rules', { method: 'POST', body: { rules: rules } }).then(function (res) {
                state.rules = res.rules || [];
                draw(); close();
                Dvx.toast(message, 'success');
            }).catch(function (err) { Dvx.toast(err.message, 'error'); });
        }

        actionSel.addEventListener('change', function () { loadForm(actionSel.value, null); });

        saveBtn.addEventListener('click', function () {
            if (!actionSel.value) { Dvx.toast('Choose what to do first.', 'error'); return; }
            var rule;
            try { rule = gather(); } catch (e) { Dvx.toast(e.message, 'error'); return; }
            var rules = state.rules.slice();
            var at = -1;
            rules.forEach(function (r, i) { if (current && r.id === current.id) { at = i; } });
            if (at > -1) { rules[at] = rule; } else { rules.push(rule); }
            saveBtn.disabled = true;
            persist(rules, 'Saved.').then(function () { saveBtn.disabled = false; });
        });

        delBtn.addEventListener('click', function () {
            if (!current || !window.confirm('Delete this rule?')) { return; }
            persist(state.rules.filter(function (r) { return r.id !== current.id; }), 'Deleted.');
        });

        Dvx.on(body, 'click', '[data-i]', function (e, tr) { edit(state.rules[Number(tr.getAttribute('data-i'))]); });
        body.addEventListener('keydown', function (e) {
            var tr = e.target.closest ? e.target.closest('[data-i]') : null;
            if (tr && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); tr.click(); }
        });
        newBtn.addEventListener('click', function () { edit(null); });
        Dvx.qs('[data-auto-close]', editor).addEventListener('click', close);
        backdrop.addEventListener('click', close);
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !editor.hidden) { close(); } });

        load();
    });
})();
