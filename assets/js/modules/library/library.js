/* Library tab: presets and templates saved in the account. List, create, edit and delete them with the same fields as the action itself. */
(function () {
    'use strict';
    var esc = Dvx.esc;

    Dvx.ready(function () {
        var root = Dvx.qs('[data-lb-root]');
        if (!root) { return; }
        var kind = root.getAttribute('data-lb-root');
        var body = Dvx.qs('[data-lb-body]', root);
        var editor = Dvx.qs('[data-lb-editor]', root);
        var backdrop = Dvx.qs('[data-lb-backdrop]', root);
        // The editor drawer lives on <body> so nothing in the page (sticky panels, the header) can sit above it.
        document.body.appendChild(backdrop);
        document.body.appendChild(editor);
        var title = Dvx.qs('[data-lb-editor-title]', editor);
        var saveBtn = Dvx.qs('[data-lb-save]', editor);
        var delBtn = Dvx.qs('[data-lb-delete]', editor);
        var useBtn = Dvx.qs('[data-lb-use]', editor);
        var limitEl = Dvx.qs('[data-lb-limit]', root);
        var cols = 3;
        var list = [];
        var current = null; // the item being edited, or null for a new one
        var allowed = null; // endpoints a plan allows presets for

        function field(name) { return Dvx.qs('[data-lb-field="' + name + '"]', editor); }

        function note(res) { if (res && res.ok === false) { Dvx.toast(res.message, 'error'); return false; } return true; }

        // ------------------------------------------------------------- list
        function opLabel(id) {
            var ops = JSON.parse(root.getAttribute('data-ops') || '[]');
            var hit = ops.filter(function (o) { return o.id === id; })[0];
            return hit ? hit.label : id;
        }

        function load() {
            Dvx.api('library/' + kind).then(function (res) {
                if (!res.ok) { body.innerHTML = '<tr><td colspan="' + cols + '">' + esc(res.message) + '</td></tr>'; return; }
                list = (res.data && res.data.results) || [];
                allowed = res.data && res.data.endpoints ? res.data.endpoints : null;
                if (limitEl) {
                    var limit = res.data && res.data.limit;
                    limitEl.textContent = limit ? list.length + ' / ' + limit + ' ' + (kind === 'presets' ? 'presets' : 'templates') : '';
                }
                if (!list.length) { body.innerHTML = '<tr><td colspan="' + cols + '"><div class="dvs-empty-state"><p>Nothing saved yet.</p></div></td></tr>'; return; }
                body.innerHTML = list.map(function (p, i) {
                    var second = kind === 'presets' ? '<td>' + esc(opLabel(p.endpoint)) + '</td>' : '<td>' + esc((p.variables || []).map(function (v) { return v.name; }).join(', ') || '-') + '</td>';
                    return '<tr class="dvx-row-click" tabindex="0" role="button" data-i="' + i + '"><td>' + esc(p.name) + (p.description ? '<div class="dvx-muted">' + esc(p.description) + '</div>' : '') + '</td>' + second +
                        '<td class="dvx-muted">' + esc((p.updatedAt || '').slice(0, 10)) + '</td></tr>';
                }).join('');
            }).catch(function (err) { body.innerHTML = '<tr><td colspan="' + cols + '">' + esc(err.message) + '</td></tr>'; });
        }

        // ----------------------------------------------------------- editor shell
        function openEditor(item) {
            current = item;
            title.textContent = item ? item.name : (kind === 'presets' ? 'New preset' : 'New template');
            delBtn.hidden = !item;
            if (useBtn) { useBtn.hidden = !item; }
            editor.hidden = false; backdrop.hidden = false;
            requestAnimationFrame(function () { editor.classList.add('is-open'); backdrop.classList.add('is-open'); });
        }
        function closeEditor() {
            editor.classList.remove('is-open'); backdrop.classList.remove('is-open');
            setTimeout(function () { if (!editor.classList.contains('is-open')) { editor.hidden = true; backdrop.hidden = true; } }, 280);
        }

        // ---------------------------------------------------------------- presets
        var paramsBox = Dvx.qs('[data-lb-params]', editor);

        function fillParams(params) {
            Dvx.qsa('[data-p]', paramsBox).forEach(function (el) {
                var key = el.getAttribute('data-p');
                if (!params || !(key in params)) { return; }
                var type = el.getAttribute('data-ptype');
                var v = params[key];
                if (type === 'switch') { el.checked = !!v; }
                else if (type === 'json') { el.value = typeof v === 'string' ? v : JSON.stringify(v, null, 2); }
                else { el.value = v; }
            });
        }

        function loadParamsForm(endpoint, params) {
            paramsBox.innerHTML = '';
            if (!endpoint) { return; }
            paramsBox.innerHTML = '<div class="dvs-empty-state"><p>Loading…</p></div>';
            Dvx.api('drawer/form', { query: { operation: endpoint } }).then(function (res) {
                paramsBox.innerHTML = '<label class="davix-h2i-label">Settings</label>' + res.html;
                Dvx.qsa('.dvx-hidden', paramsBox).forEach(function (n) { n.classList.remove('dvx-hidden'); });
                Dvx.qsa('[data-dvx-drawer-mode]', paramsBox).forEach(function (n) { n.closest('.davix-h2i-field-group').remove(); });
                fillParams(params);
            }).catch(function (err) { paramsBox.innerHTML = '<p class="dvx-muted">' + esc(err.message) + '</p>'; });
        }

        function setupPresetEditor() {
            var select = field('endpoint');
            var ops = JSON.parse(root.getAttribute('data-ops') || '[]').filter(function (o) { return !allowed || allowed.indexOf(o.id) !== -1; });
            select.innerHTML = '<option value="">Choose an action…</option>' + ops.map(function (o) { return '<option value="' + esc(o.id) + '">' + esc(o.label) + '</option>'; }).join('');
            select.addEventListener('change', function () { loadParamsForm(select.value, null); });
        }

        function editPreset(item) {
            openEditor(item);
            field('name').value = item ? item.name : '';
            var select = field('endpoint');
            select.disabled = !!item;
            if (item && !Dvx.qs('option[value="' + item.endpoint + '"]', select)) {
                select.insertAdjacentHTML('beforeend', '<option value="' + esc(item.endpoint) + '">' + esc(opLabel(item.endpoint)) + '</option>');
            }
            select.value = item ? item.endpoint : '';
            loadParamsForm(item ? item.endpoint : '', item ? item.parameters : null);
        }

        function savePreset() {
            var name = field('name').value.trim();
            var endpoint = field('endpoint').value;
            if (!name) { Dvx.toast('Give the preset a name.', 'error'); return Promise.resolve(); }
            if (!endpoint) { Dvx.toast('Choose an action.', 'error'); return Promise.resolve(); }
            var params;
            try { params = window.Dvx.collectParams(paramsBox); } catch (e) { Dvx.toast(e.message, 'error'); return Promise.resolve(); }
            var req = current
                ? Dvx.api('library/presets/' + current.id, { method: 'PUT', body: { name: name, parameters: params } })
                : Dvx.api('library/presets', { method: 'POST', body: { name: name, endpoint: endpoint, parameters: params } });
            return req;
        }

        // -------------------------------------------------------------- templates
        var varsBox = Dvx.qs('[data-lb-vars]', editor);
        var kept = {}; // details typed for a variable, kept while its name stays in the text

        function readVars() {
            Dvx.qsa('[data-var]', varsBox).forEach(function (row) {
                kept[row.getAttribute('data-var')] = {
                    name: row.getAttribute('data-var'),
                    default: Dvx.qs('[data-v="default"]', row).value,
                    required: Dvx.qs('[data-v="required"]', row).checked,
                    description: Dvx.qs('[data-v="description"]', row).value
                };
            });
        }

        function detect() {
            var text = (field('html') ? field('html').value : '') + ' ' + (field('css') ? field('css').value : '');
            var names = [];
            var re = /\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/g, m;
            while ((m = re.exec(text))) { if (names.indexOf(m[1]) === -1) { names.push(m[1]); } }
            return names;
        }

        function renderVars() {
            readVars();
            var names = detect();
            if (!names.length) { varsBox.innerHTML = '<p class="dvx-muted">No {{variables}} in the HTML yet.</p>'; return; }
            varsBox.innerHTML = names.map(function (n) {
                var v = kept[n] || { default: '', required: false, description: '' };
                return '<div class="dvx-var" data-var="' + esc(n) + '"><div class="dvx-var__name"><code>{{' + esc(n) + '}}</code>' +
                    '<label class="dvx-var__req"><input type="checkbox" data-v="required"' + (v.required ? ' checked' : '') + ' /> Required</label></div>' +
                    '<input type="text" class="davix-h2i-input" data-v="default" placeholder="Default value" value="' + esc(v.default || '') + '" />' +
                    '<input type="text" class="davix-h2i-input" data-v="description" placeholder="Description" value="' + esc(v.description || '') + '" /></div>';
            }).join('');
        }

        function editTemplate(item) {
            openEditor(item);
            kept = {};
            field('name').value = item ? item.name : '';
            field('description').value = item ? (item.description || '') : '';
            field('html').value = '';
            field('css').value = '';
            varsBox.innerHTML = '';
            if (!item) { renderVars(); return; }
            Dvx.api('library/templates/' + item.id).then(function (res) {
                if (!note(res)) { return; }
                var t = res.data;
                field('html').value = t.html || '';
                field('css').value = t.css || '';
                (t.variables || []).forEach(function (v) { kept[v.name] = v; });
                renderVars();
            }).catch(function (err) { Dvx.toast(err.message, 'error'); });
        }

        function saveTemplate() {
            var name = field('name').value.trim();
            if (!name) { Dvx.toast('Give the template a name.', 'error'); return Promise.resolve(); }
            if (!field('html').value.trim()) { Dvx.toast('The HTML cannot be empty.', 'error'); return Promise.resolve(); }
            readVars();
            var vars = detect().map(function (n) { return kept[n] || { name: n, required: false, description: '' }; });
            var body = { name: name, description: field('description').value, html: field('html').value, css: field('css').value, variables: vars };
            return current
                ? Dvx.api('library/templates/' + current.id, { method: 'PUT', body: body })
                : Dvx.api('library/templates', { method: 'POST', body: body });
        }

        // ------------------------------------------------------------------ wiring
        if (kind === 'presets') { setupPresetEditor(); }
        if (kind === 'templates') {
            ['html', 'css'].forEach(function (k) { field(k).addEventListener('input', renderVars); });
            varsBox.addEventListener('change', readVars);
        }

        Dvx.on(root, 'click', '[data-lb-new]', function () { (kind === 'presets' ? editPreset : editTemplate)(null); });
        Dvx.on(root, 'click', '[data-i]', function (e, tr) {
            var item = list[Number(tr.getAttribute('data-i'))];
            if (item) { (kind === 'presets' ? editPreset : editTemplate)(item); }
        });
        body.addEventListener('keydown', function (e) {
            var tr = e.target.closest ? e.target.closest('[data-i]') : null;
            if (tr && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); tr.click(); }
        });
        Dvx.qs('[data-lb-close]', editor).addEventListener('click', closeEditor);
        backdrop.addEventListener('click', closeEditor);
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !editor.hidden && !document.body.classList.contains('dvx-modal-open')) { closeEditor(); } });

        saveBtn.addEventListener('click', function () {
            var req = kind === 'presets' ? savePreset() : saveTemplate();
            saveBtn.disabled = true;
            req.then(function (res) {
                if (res && note(res)) { Dvx.toast('Saved.', 'success'); closeEditor(); load(); }
            }).catch(function (err) { Dvx.toast(err.message, 'error'); }).then(function () { saveBtn.disabled = false; });
        });

        delBtn.addEventListener('click', function () {
            if (!current || !window.confirm(Dvx.i18n.confirm)) { return; }
            Dvx.api('library/' + kind + '/' + current.id, { method: 'DELETE' }).then(function (r) {
                if (note(r)) { closeEditor(); load(); }
            });
        });

        if (useBtn) {
            useBtn.addEventListener('click', function () {
                if (!current) { return; }
                Dvx.api('library/templates/' + current.id + '/use', { method: 'POST' }).then(function (r) {
                    Dvx.toast(r.ok === false ? r.message : 'Template loaded into H2I Render.', r.ok === false ? 'error' : 'success');
                });
            });
        }

        load();
    });
})();
