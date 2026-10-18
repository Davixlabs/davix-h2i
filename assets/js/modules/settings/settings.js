/* Settings tab, Import / Export: download chosen sections, load a file (drag and drop or click) and choose what to import, reset everything. */
(function () {
    'use strict';
    var esc = Dvx.esc;

    Dvx.ready(function () {
        var root = Dvx.qs('[data-st-transfer]');
        if (!root) { return; }
        var exportList = Dvx.qs('[data-st-export-list]', root);
        var exp = Dvx.qs('[data-st-export]', root);
        var drop = Dvx.qs('[data-st-drop]', root);
        var file = Dvx.qs('[data-st-file]', root);
        var dropTitle = Dvx.qs('[data-st-drop-title]', root);
        var dropSub = Dvx.qs('[data-st-drop-sub]', root);
        var importList = Dvx.qs('[data-st-import-list]', root);
        var note = Dvx.qs('[data-st-import-note]', root);
        var imp = Dvx.qs('[data-st-import]', root);
        var loaded = null;

        // Section names and descriptions come from the export list, so both lists always say the same thing.
        var labels = {};
        Dvx.qsa('.dvx-pick__item', exportList).forEach(function (item) {
            labels[Dvx.qs('input', item).value] = { title: Dvx.qs('strong', item).textContent, sub: Dvx.qs('small', item).textContent };
        });

        function checked(list) {
            return Dvx.qsa('input:checked', list).map(function (i) { return i.value; });
        }

        function refreshButtons() {
            exp.disabled = !checked(exportList).length;
            imp.disabled = !loaded || !checked(importList).length;
        }
        exportList.addEventListener('change', refreshButtons);
        importList.addEventListener('change', refreshButtons);

        // ------------------------------------------------------------- export
        exp.addEventListener('click', function () {
            exp.disabled = true;
            Dvx.api('settings/export', { query: { sections: checked(exportList).join(',') } }).then(function (data) {
                var blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
                var a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'davix-h2i-settings-' + new Date().toISOString().slice(0, 10) + '.json';
                document.body.appendChild(a); a.click(); a.remove();
                Dvx.toast('Settings file downloaded.', 'success');
            }).catch(function (err) { Dvx.toast(err.message, 'error'); }).then(refreshButtons);
        });

        // ------------------------------------------------------------- import
        function showNote(text, tone) {
            note.className = 'dvx-callout' + (tone ? ' is-' + tone : '');
            note.innerHTML = '<p>' + esc(text) + '</p>';
        }

        function reset() {
            loaded = null;
            file.value = '';
            drop.classList.remove('has-file');
            dropTitle.textContent = 'Drop a settings file here';
            dropSub.textContent = 'or click to choose a .json file';
            importList.classList.add('dvx-hidden'); importList.innerHTML = '';
            note.classList.add('dvx-hidden');
            refreshButtons();
        }

        function read(f) {
            if (!f) { return; }
            var reader = new FileReader();
            reader.onload = function () {
                var data;
                try { data = JSON.parse(reader.result); } catch (e) { reset(); note.classList.remove('dvx-hidden'); showNote('That is not a valid settings file.', 'danger'); return; }
                if (!data || data.plugin !== 'davix-h2i' || !data.sections || typeof data.sections !== 'object') {
                    reset(); note.classList.remove('dvx-hidden'); showNote('This is not a Davix H2I settings file.', 'danger'); return;
                }
                var found = Object.keys(data.sections).filter(function (s) { return labels[s]; });
                if (!found.length) { reset(); note.classList.remove('dvx-hidden'); showNote('The file has no settings this plugin can use.', 'danger'); return; }
                loaded = data;
                drop.classList.add('has-file');
                dropTitle.textContent = f.name;
                dropSub.textContent = (data.exported ? 'Exported ' + data.exported.slice(0, 10) + ' · ' : '') + 'plugin version ' + (data.version || '?') + ' · click to choose another file';
                importList.innerHTML = found.map(function (s) {
                    return '<label class="dvx-pick__item"><input type="checkbox" value="' + esc(s) + '" checked /><span class="dvx-pick__text"><strong>' + esc(labels[s].title) + '</strong><small>' + esc(labels[s].sub) + '</small></span></label>';
                }).join('');
                importList.classList.remove('dvx-hidden');
                note.classList.remove('dvx-hidden');
                showNote('Importing replaces the current settings of every section you tick.', 'warning');
                refreshButtons();
            };
            reader.readAsText(f);
        }

        file.addEventListener('change', function () { read(file.files[0]); });
        ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-over'); }); });
        ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('is-over'); }); });
        drop.addEventListener('drop', function (e) { if (e.dataTransfer && e.dataTransfer.files.length) { read(e.dataTransfer.files[0]); } });

        imp.addEventListener('click', function () {
            if (!loaded) { return; }
            imp.disabled = true;
            Dvx.api('settings/import', { method: 'POST', body: Object.assign({}, loaded, { only: checked(importList) }) })
                .then(function (res) {
                    var names = (res.imported || []).map(function (s) { return labels[s] ? labels[s].title : s; });
                    Dvx.toast('Imported: ' + names.join(', ') + '.', 'success');
                    reset();
                })
                .catch(function (err) { Dvx.toast(err.message, 'error'); refreshButtons(); });
        });

        // -------------------------------------------------------------- reset
        var rst = Dvx.qs('[data-st-reset]');
        if (rst) {
            rst.addEventListener('click', function () {
                if (!window.confirm('Reset every setting to its default? This cannot be undone.')) { return; }
                rst.disabled = true;
                var sections = rst.getAttribute('data-sections').split(',');
                sections.reduce(function (p, s) { return p.then(function () { return Dvx.api('settings/' + s + '/reset', { method: 'POST' }); }); }, Promise.resolve())
                    .then(function () { Dvx.toast('All settings are back to their defaults.', 'success'); })
                    .catch(function (err) { Dvx.toast(err.message, 'error'); })
                    .then(function () { rst.disabled = false; });
            });
        }

        refreshButtons();
    });
})();
