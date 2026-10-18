/* Davix H2I: shared helpers (REST client with nonce, DOM helpers, toasts, formatting). Loaded first on every plugin screen. */
(function () {
    'use strict';

    var C = window.DavixH2I || {};

    function esc(value) {
        var d = document.createElement('div');
        d.textContent = value == null ? '' : String(value);
        return d.innerHTML;
    }

    function api(path, options) {
        options = options || {};
        var method = (options.method || 'GET').toUpperCase();
        var url = C.rest + String(path).replace(/^\//, '');
        if (options.query) {
            var q = [];
            Object.keys(options.query).forEach(function (k) {
                var v = options.query[k];
                if (v !== undefined && v !== null && v !== '') { q.push(encodeURIComponent(k) + '=' + encodeURIComponent(v)); }
            });
            if (q.length) { url += (url.indexOf('?') > -1 ? '&' : '?') + q.join('&'); }
        }
        var init = { method: method, credentials: 'same-origin', headers: { 'X-WP-Nonce': C.nonce, 'Accept': 'application/json' } };
        if (options.body !== undefined) {
            init.headers['Content-Type'] = 'application/json';
            init.body = JSON.stringify(options.body);
        }
        return fetch(url, init).then(function (res) {
            return res.text().then(function (text) {
                var data = null;
                try { data = text ? JSON.parse(text) : null; } catch (e) { data = null; }
                if (!res.ok) {
                    var err = new Error((data && data.message) || res.statusText || 'Request failed');
                    err.status = res.status;
                    err.data = data;
                    throw err;
                }
                return data;
            });
        });
    }

    function toast(message, type) {
        if (window.DavixNC && document.querySelector('#davix-h2i-admin')) {
            window.DavixNC.push(message, type);
            return;
        }
        var root = document.getElementById('davix-h2i-toast-root');
        if (!root) {
            root = document.createElement('div');
            root.id = 'davix-h2i-toast-root';
            document.body.appendChild(root);
        }
        var el = document.createElement('div');
        el.className = 'davix-h2i-toast is-' + (type || 'info');
        el.setAttribute('role', 'status');
        el.innerHTML = '<span class="davix-h2i-toast-message">' + esc(message) + '</span><button type="button" class="davix-h2i-toast-close" aria-label="Close">&times;</button>';
        root.appendChild(el);
        var remove = function () { if (el.parentNode) { el.parentNode.removeChild(el); } };
        el.querySelector('button').addEventListener('click', remove);
        setTimeout(remove, type === 'error' ? 8000 : 4000);
    }

    function bytes(n) {
        n = Number(n) || 0;
        if (n < 1024) { return n + ' B'; }
        if (n < 1048576) { return (n / 1024).toFixed(1) + ' KB'; }
        return (n / 1048576).toFixed(2) + ' MB';
    }

    function on(root, event, selector, handler) {
        root.addEventListener(event, function (e) {
            var t = e.target.closest ? e.target.closest(selector) : null;
            if (t && root.contains(t)) { handler.call(t, e, t); }
        });
    }

    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); } else { document.addEventListener('DOMContentLoaded', fn); }
    }

    function qs(sel, root) { return (root || document).querySelector(sel); }
    function qsa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

    /** Builds a nested object from a form's field names: a[b][c]=1, list[]=x. */
    function formData(form) {
        var out = {};
        qsa('input, select, textarea', form).forEach(function (field) {
            if (!field.name || field.disabled) { return; }
            if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) {
                if (field.type === 'checkbox' && /\[\]$/.test(field.name)) { return; }
                return;
            }
            if (field.type === 'file') { return; }
            var parts = field.name.replace(/\]/g, '').split('[');
            var cur = out;
            for (var i = 0; i < parts.length; i++) {
                var key = parts[i];
                var last = i === parts.length - 1;
                if (key === '') {
                    // list: parent is an array
                    return;
                }
                if (last) {
                    cur[key] = field.value;
                } else {
                    var next = parts[i + 1];
                    if (next === '') {
                        if (!Array.isArray(cur[key])) { cur[key] = []; }
                        cur[key].push(field.value);
                        return;
                    }
                    cur[key] = cur[key] || {};
                    cur = cur[key];
                }
            }
        });
        return out;
    }

    // A "choose from the Media Library" field ([data-dvx-media]): the hidden input holds the file's id.
    document.addEventListener('click', function (e) {
        var pick = e.target.closest ? e.target.closest('[data-dvx-media-pick]') : null;
        var clear = e.target.closest ? e.target.closest('[data-dvx-media-clear]') : null;
        var box = (pick || clear) ? (pick || clear).closest('[data-dvx-media]') : null;
        if (!box) { return; }
        var input = box.querySelector('input[type="hidden"]');
        var label = box.querySelector('[data-dvx-media-label]');
        var clearBtn = box.querySelector('[data-dvx-media-clear]');
        if (clear) {
            input.value = ''; label.textContent = ''; clearBtn.hidden = true;
            input.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }
        if (!window.wp || !window.wp.media) { toast('The Media Library is not available here.', 'error'); return; }
        var frame = window.wp.media({ title: (C.i18n && C.i18n.chooseFiles) || 'Choose a file', multiple: false, library: { type: 'image' } });
        frame.on('select', function () {
            var a = frame.state().get('selection').first().toJSON();
            input.value = a.id; label.textContent = a.title || a.filename; clearBtn.hidden = false;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
        frame.open();
    });

    // A table of image sizes ([data-dvx-sizes]): rows are edited here, a hidden textarea holds them as JSON for the form.
    (function () {
        var FIT = { fit: 'Fit inside', cover: 'Fill and crop', pad: 'Pad', force: 'Force exact size' };
        var FORMAT = ['jpeg', 'png', 'webp', 'avif', 'gif', 'tiff'];
        function opts(map, current) {
            var keys = Array.isArray(map) ? map : Object.keys(map);
            return keys.map(function (k) { return '<option value="' + esc(k) + '"' + (k === current ? ' selected' : '') + '>' + esc(Array.isArray(map) ? k.toUpperCase() : map[k]) + '</option>'; }).join('');
        }
        function rowHtml(r) {
            r = r || { name: '', width: '', height: '', fit: 'fit', format: 'webp', quality: 80 };
            return '<tr><td><input class="davix-h2i-input" data-s="name" value="' + esc(r.name || '') + '" placeholder="small" /></td>' +
                '<td><input class="davix-h2i-input" type="number" min="1" data-s="width" value="' + esc(r.width || '') + '" /></td>' +
                '<td><input class="davix-h2i-input" type="number" min="0" data-s="height" value="' + esc(r.height || '') + '" /></td>' +
                '<td><select class="davix-h2i-select" data-s="fit">' + opts(FIT, r.fit || 'fit') + '</select></td>' +
                '<td><select class="davix-h2i-select" data-s="format">' + opts(FORMAT, r.format || 'webp') + '</select></td>' +
                '<td><input class="davix-h2i-input" type="number" min="1" max="100" data-s="quality" value="' + esc(r.quality || 80) + '" /></td>' +
                '<td><button type="button" class="dvs-btn" data-dvx-sizes-del aria-label="Remove">&times;</button></td></tr>';
        }
        function sync(box) {
            var out = [];
            qsa('tbody tr', box).forEach(function (tr) {
                var get = function (k) { return qs('[data-s="' + k + '"]', tr).value; };
                var name = get('name').trim().replace(/[^A-Za-z0-9_-]/g, '');
                if (!name || !Number(get('width'))) { return; }
                var row = { name: name, width: Number(get('width')), fit: get('fit'), format: get('format'), quality: Number(get('quality')) || 80 };
                if (Number(get('height')) > 0) { row.height = Number(get('height')); }
                out.push(row);
            });
            qs('textarea', box).value = JSON.stringify(out);
        }
        function build(box) {
            if (box.getAttribute('data-built')) { return; }
            box.setAttribute('data-built', '1');
            var rows = [];
            try { rows = JSON.parse(qs('textarea', box).value || '[]'); } catch (e) { rows = []; }
            qs('tbody', box).innerHTML = rows.map(rowHtml).join('');
            sync(box);
        }
        function scan() { qsa('[data-dvx-sizes]').forEach(build); }
        document.addEventListener('input', function (e) { var b = e.target.closest ? e.target.closest('[data-dvx-sizes]') : null; if (b) { sync(b); } });
        document.addEventListener('change', function (e) { var b = e.target.closest ? e.target.closest('[data-dvx-sizes]') : null; if (b) { sync(b); } });
        document.addEventListener('click', function (e) {
            var b = e.target.closest ? e.target.closest('[data-dvx-sizes]') : null;
            if (!b) { return; }
            if (e.target.closest('[data-dvx-sizes-add]')) { qs('tbody', b).insertAdjacentHTML('beforeend', rowHtml()); sync(b); }
            var del = e.target.closest('[data-dvx-sizes-del]');
            if (del) { del.closest('tr').remove(); sync(b); }
        });
        // Forms are loaded on demand (the sidebar, side panels), so look again whenever the page changes.
        new MutationObserver(scan).observe(document.documentElement, { childList: true, subtree: true });
        ready(scan);
    })();

    window.Dvx = {
        config: C,
        i18n: C.i18n || {},
        esc: esc,
        api: api,
        toast: toast,
        bytes: bytes,
        on: on,
        ready: ready,
        qs: qs,
        qsa: qsa,
        formData: formData
    };
})();
