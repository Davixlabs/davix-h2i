/* History tab: filters, table, detail side panel, restore, export, clear. Only actions made through this plugin are listed. */
(function () {
    'use strict';
    var esc = Dvx.esc;
    var cfg = window.DavixH2I_davix_h2i_history || { operations: {} };

    var BADGE = {
        success: ['Done', 'success'], failed: ['Failed', 'danger'], pending_review: ['Needs review', 'warning'], restored: ['Restored', 'info'], rejected: ['Rejected', 'danger']
    };
    var SOURCE = { tab: 'Plugin screen', drawer: 'Media Library', editor: 'Editor', upload: 'Upload', publish: 'Post published', bulk: 'Bulk run', generator: 'Generator', library: 'Media Library', tools: 'Tools', cli: 'WP-CLI' };

    function badge(status) {
        var b = BADGE[status] || [status, 'info'];
        return '<span class="dvs-badge dvs-badge--' + b[1] + '">' + esc(b[0]) + '</span>';
    }

    function change(row) {
        if (!row.bytes_before || !row.bytes_after) { return '<span class="dvx-muted">-</span>'; }
        var pct = Math.round(100 - 100 * row.bytes_after / row.bytes_before);
        return esc(Dvx.bytes(row.bytes_before) + ' → ' + Dvx.bytes(row.bytes_after)) + (pct > 0 ? ' <span class="dvs-badge dvs-badge--success">-' + pct + '%</span>' : '');
    }

    Dvx.ready(function () {
        var root = Dvx.qs('[data-hx-root]');
        if (!root) { return; }
        var body = Dvx.qs('[data-hx-body]', root);
        var count = Dvx.qs('[data-hx-count]', root);
        var prev = Dvx.qs('[data-hx-prev]', root);
        var next = Dvx.qs('[data-hx-next]', root);
        var side = Dvx.qs('[data-hx-side]');
        var sideBody = Dvx.qs('[data-hx-side-body]');
        var sideFoot = Dvx.qs('[data-hx-side-foot]');
        var sideTitle = Dvx.qs('[data-hx-side-title]');
        var backdrop = Dvx.qs('[data-hx-backdrop]');
        // On <body>, so no sticky panel or header can sit above the drawer.
        document.body.appendChild(backdrop);
        document.body.appendChild(side);
        var page = 1, per = 20, total = 0, rows = [];

        function filters() {
            var f = {};
            Dvx.qsa('[data-hx-filter]', root).forEach(function (el) { if (el.value) { f[el.getAttribute('data-hx-filter')] = el.value; } });
            return f;
        }

        function load() {
            var q = filters(); q.page = page; q.per_page = per;
            Dvx.api('history', { query: q }).then(function (res) {
                rows = res.items; total = res.total;
                if (!rows.length) {
                    body.innerHTML = '<tr><td colspan="6"><div class="dvs-empty-state"><span class="dashicons dashicons-backup"></span><p>Nothing here yet. Actions you run from this plugin will show up here.</p></div></td></tr>';
                } else {
                    body.innerHTML = rows.map(function (r, i) {
                        return '<tr data-dvx-row data-i="' + i + '"><td><div class="dvx-cell">' +
                            (r.thumb ? '<img class="dvx-thumb" src="' + esc(r.thumb) + '" alt="" />' : '<span class="dvx-thumb dvx-thumb--none"><span class="dashicons dashicons-format-image"></span></span>') +
                            '<div class="dvx-cell__text"><div class="dvx-cell__title">' + esc(r.object_title || '-') + '</div><div class="dvx-muted">' + esc(SOURCE[r.source] || r.source) + '</div></div></div></td>' +
                            '<td>' + esc(r.label) + '</td><td>' + badge(r.status) + (r.error_message ? '<div class="dvx-muted">' + esc(r.error_message) + '</div>' : '') + '</td>' +
                            '<td class="dvx-num">' + change(r) + '</td><td>' + esc(r.user_name || '-') + '</td><td title="' + esc(r.when) + '">' + esc(r.ago) + ' ago</td></tr>';
                    }).join('');
                }
                var from = total ? (page - 1) * per + 1 : 0;
                count.textContent = from + '-' + Math.min(total, page * per) + ' of ' + total;
                prev.disabled = page <= 1;
                next.disabled = page * per >= total;
                var s = res.stats;
                var cards = Dvx.qsa('.dvx-stat__value', root);
                if (cards.length >= 4) { cards[0].textContent = s.total; cards[1].textContent = s.success; cards[2].textContent = s.failed; cards[3].textContent = Dvx.bytes(s.saved_bytes); }
            }).catch(function (err) { body.innerHTML = '<tr><td colspan="6">' + esc(err.message) + '</td></tr>'; });
        }

        function closeSide() {
            side.classList.remove('is-open'); backdrop.classList.remove('is-open');
            setTimeout(function () { if (!side.classList.contains('is-open')) { side.hidden = true; backdrop.hidden = true; } }, 280);
        }

        function dl(label, value) { return value ? '<dt>' + esc(label) + '</dt><dd>' + value + '</dd>' : ''; }

        function openRow(id) {
            Dvx.api('history/' + id).then(function (r) {
                // The header is the file's name; everything below is written for a person: no codes, no raw settings.
                sideTitle.textContent = r.object_title || r.label;
                var h = '<dl>' + dl('Action', esc(r.label)) + dl('Result', badge(r.status)) +
                    dl('File', r.file_gone ? esc(r.object_title) + ' <span class="dvx-muted">(deleted from your Media Library)</span>' : r.edit_url ? '<a href="' + esc(r.edit_url) + '">' + esc(r.object_title) + '</a>' : esc(r.object_title)) +
                    dl('When', esc(r.when)) + dl('Done by', esc(r.user_name)) + dl('Started from', esc(SOURCE[r.source] || r.source)) +
                    dl('Size', r.bytes_before ? change(r) : '') + dl('Support reference', r.request_id ? '<code>' + esc(r.request_id) + '</code>' : '') + '</dl>';
                if (r.error_message) { h += '<div class="dvx-callout is-danger"><p>' + esc(r.error_message) + '</p></div>'; }
                var res = r.result || {};
                if (res.outputs && res.outputs.length) {
                    h += '<h4>Result files</h4><div class="dvx-outputs">';
                    res.outputs.forEach(function (o) {
                        if (o.gone) { h += '<div class="dvx-output-gone"><span class="dashicons dashicons-trash"></span><span>No longer in your Media Library</span></div>'; }
                        else if (o.thumb || o.url) { h += '<a href="' + esc(o.edit_url || o.url) + '" target="_blank" rel="noopener"><img src="' + esc(o.thumb || o.url) + '" alt="" /></a>'; }
                    });
                    h += '</div>';
                }
                var textKeys = res.text ? Object.keys(res.text).filter(function (k) { var v = res.text[k]; return v !== '' && v !== null && !(Array.isArray(v) && !v.length); }) : [];
                if (textKeys.length) {
                    h += '<h4>Text written</h4><dl>' + textKeys.map(function (k) { var v = res.text[k]; return dl(k.replace(/_/g, ' ').replace(/^./, function (c) { return c.toUpperCase(); }), esc(Array.isArray(v) ? v.join(', ') : (typeof v === 'object' ? Object.keys(v).map(function (x) { return x + ': ' + v[x]; }).join(', ') : String(v)))); }).join('') + '</dl>';
                }
                if (r.settings && r.settings.length) {
                    h += '<h4>Settings used</h4><dl>' + r.settings.map(function (s) { return dl(s.label, esc(s.value)); }).join('') + '</dl>';
                }
                if (r.kept && r.kept.length) {
                    h += '<h4>Kept by Davix H2I</h4><dl>' + r.kept.map(function (k) { return dl(k.label, esc(k.value)); }).join('') + '</dl>';
                }
                sideBody.innerHTML = h;
                var foot = '';
                if (r.status === 'pending_review') { foot += '<button class="dvs-save-button" data-act="approve">Apply</button><button class="dvs-btn" data-act="reject">Reject</button>'; }
                if (r.can_restore) { foot += '<button class="dvs-btn" data-act="restore">Restore original</button>'; }
                if (r.edit_url) { foot += '<a class="dvs-btn" href="' + esc(r.edit_url) + '">Open file</a>'; }
                if (cfg.canManage !== false) { foot += '<button class="dvs-btn dvs-btn--danger" data-act="delete">Remove from history</button>'; }
                sideFoot.innerHTML = foot;
                sideFoot.setAttribute('data-id', r.id);
                side.hidden = false; backdrop.hidden = false;
                requestAnimationFrame(function () { side.classList.add('is-open'); backdrop.classList.add('is-open'); });
            }).catch(function (err) { Dvx.toast(err.message, 'error'); });
        }

        // A picture that cannot load (the file was replaced or deleted) becomes a plain placeholder, never a broken image.
        document.addEventListener('error', function (e) {
            var img = e.target;
            if (!img || img.tagName !== 'IMG' || !root.contains(img) && !(side && side.contains(img))) { return; }
            var holder = document.createElement('span');
            if (img.classList.contains('dvx-thumb')) {
                holder.className = 'dvx-thumb dvx-thumb--none';
                holder.innerHTML = '<span class="dashicons dashicons-format-image"></span>';
                img.replaceWith(holder);
            } else if (img.closest('.dvx-outputs')) {
                holder.className = 'dvx-output-gone';
                holder.innerHTML = '<span class="dashicons dashicons-format-image"></span><span>Preview not available</span>';
                (img.closest('a') || img).replaceWith(holder);
            }
        }, true);

        Dvx.on(root, 'click', '[data-dvx-row]', function (e, tr) { openRow(rows[Number(tr.getAttribute('data-i'))].id); });
        backdrop.addEventListener('click', closeSide);
        Dvx.qs('[data-hx-close]').addEventListener('click', closeSide);
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeSide(); } });

        sideFoot.addEventListener('click', function (e) {
            var b = e.target.closest('[data-act]');
            if (!b) { return; }
            var id = sideFoot.getAttribute('data-id');
            var act = b.getAttribute('data-act');
            if ((act === 'delete' || act === 'restore') && !window.confirm(Dvx.i18n.confirm)) { return; }
            var req = act === 'delete' ? Dvx.api('history/' + id, { method: 'DELETE' }) : Dvx.api('history/' + id + '/' + act, { method: 'POST' });
            req.then(function (res) { Dvx.toast(res.message || Dvx.i18n.done, 'success'); closeSide(); load(); }).catch(function (err) { Dvx.toast(err.message, 'error'); });
        });

        var timer;
        Dvx.qsa('[data-hx-filter]', root).forEach(function (el) {
            el.addEventListener(el.tagName === 'INPUT' && el.type === 'search' ? 'input' : 'change', function () {
                clearTimeout(timer); timer = setTimeout(function () { page = 1; load(); }, 250);
            });
        });
        prev.addEventListener('click', function () { page = Math.max(1, page - 1); load(); });
        next.addEventListener('click', function () { page += 1; load(); });

        var exportBtn = Dvx.qs('[data-hx-export]');
        if (exportBtn) {
            exportBtn.addEventListener('click', function () {
                Dvx.api('history/export', { query: filters() }).then(function (res) {
                    var blob = new Blob([res.csv], { type: 'text/csv;charset=utf-8' });
                    var a = document.createElement('a');
                    a.href = URL.createObjectURL(blob); a.download = res.filename; document.body.appendChild(a); a.click(); a.remove();
                });
            });
        }
        var clearBtn = Dvx.qs('[data-hx-clear]');
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (!window.confirm('Remove all history entries? This does not change your files.')) { return; }
                Dvx.api('history/clear', { method: 'POST' }).then(function () { Dvx.toast('History cleared.', 'success'); page = 1; load(); });
            });
        }
        load();
    });
})();
