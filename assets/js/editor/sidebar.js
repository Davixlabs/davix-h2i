/* Block editor sidebar: writing help for the post you are editing and tools for its featured image.
   Written without a build step: plain wp.element calls. Results are shown for you to use; nothing changes until you press a button. */
(function (wp) {
    'use strict';
    if (!wp || !wp.plugins || !wp.editPost || !wp.element) { return; }

    var el = wp.element.createElement;
    var useState = wp.element.useState;
    var __ = wp.i18n.__;
    var PluginSidebar = wp.editPost.PluginSidebar;
    var PluginSidebarMoreMenuItem = wp.editPost.PluginSidebarMoreMenuItem;
    var PanelBody = wp.components.PanelBody;
    var Button = wp.components.Button;
    var Notice = wp.components.Notice;
    var Spinner = wp.components.Spinner;
    var TextareaControl = wp.components.TextareaControl;
    var cfg = window.DavixH2I || {};

    function api(path, body) {
        return window.fetch(cfg.rest + path, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
            body: JSON.stringify(body)
        }).then(function (res) { return res.json().then(function (data) { if (!res.ok) { throw new Error(data.message || 'Request failed'); } return data; }); });
    }

    function allowed(op) { var g = (cfg.gate || {})[op]; return !g || (g.state !== 'locked' && g.state !== 'unavailable'); }

    var WRITING = [
        { op: 'ai.content.seo', label: __('SEO title and description', 'davix-h2i') },
        { op: 'ai.documents.summarize', label: __('Summary', 'davix-h2i') },
        { op: 'ai.content.title', label: __('Title ideas', 'davix-h2i') },
        { op: 'ai.content.social-post', label: __('Social post', 'davix-h2i') },
        { op: 'ai.documents.correct', label: __('Fix grammar and spelling', 'davix-h2i') }
    ];

    function postText() {
        var raw = wp.data.select('core/editor').getEditedPostContent() || '';
        var div = document.createElement('div');
        div.innerHTML = raw;
        return (div.textContent || '').trim().slice(0, 8000);
    }

    function lines(text) {
        return Object.keys(text || {}).filter(function (k) { var v = text[k]; return v !== '' && v !== null && !(Array.isArray(v) && !v.length); }).map(function (k) {
            var v = text[k];
            return { key: k, value: Array.isArray(v) ? v.join(', ') : (typeof v === 'object' ? JSON.stringify(v) : String(v)) };
        });
    }

    function Writing() {
        var s = useState(null), busy = s[0], setBusy = s[1];
        var r = useState(null), result = r[0], setResult = r[1];
        var e = useState(''), error = e[0], setError = e[1];

        function run(op) {
            var text = postText();
            if (text.length < 20) { setError(__('Write a little more first.', 'davix-h2i')); return; }
            setError(''); setResult(null); setBusy(op);
            api('run', { operation: op, params: { text: text }, source: 'editor' })
                .then(function (out) { setBusy(null); if (!out.ok) { setError(out.message); } else { setResult({ op: op, text: out.text }); } })
                .catch(function (err) { setBusy(null); setError(err.message); });
        }

        function use(key, value) {
            var edit = wp.data.dispatch('core/editor').editPost;
            if (key === 'summary' || key === 'meta_description' || key === 'post_text') { edit({ excerpt: value }); }
            if (key === 'title') { edit({ title: value }); }
        }

        return el('div', null,
            WRITING.filter(function (w) { return allowed(w.op); }).map(function (w) {
                return el(Button, { key: w.op, variant: 'secondary', isBusy: busy === w.op, disabled: !!busy, onClick: function () { run(w.op); }, style: { marginRight: 8, marginBottom: 8 } }, w.label);
            }),
            busy ? el(Spinner) : null,
            error ? el(Notice, { status: 'error', isDismissible: false }, error) : null,
            result ? el('div', null, lines(result.text).map(function (l) {
                var usable = ['summary', 'meta_description', 'title', 'post_text'].indexOf(l.key) > -1;
                return el('div', { key: l.key, style: { marginBottom: 12 } },
                    el(TextareaControl, { label: l.key.replace(/_/g, ' '), value: l.value, readOnly: true, rows: 3 }),
                    usable ? el(Button, { variant: 'link', onClick: function () { use(l.key, l.value); } }, l.key === 'title' ? __('Use as title', 'davix-h2i') : __('Use as excerpt', 'davix-h2i')) : null);
            })) : null);
    }

    function Featured() {
        var featuredId = wp.data.useSelect(function (select) { return select('core/editor').getEditedPostAttribute('featured_media'); }, []);
        var b = useState(null), busy = b[0], setBusy = b[1];
        var m = useState(''), message = m[0], setMessage = m[1];

        if (!featuredId) { return el('p', null, __('Set a featured image to use these.', 'davix-h2i')); }

        function run(op) {
            setBusy(op); setMessage('');
            api('run', { operation: op, object_id: featuredId, source: 'editor' })
                .then(function (out) { setBusy(null); setMessage(out.message || (out.ok ? __('Done.', 'davix-h2i') : __('Failed', 'davix-h2i'))); })
                .catch(function (err) { setBusy(null); setMessage(err.message); });
        }

        return el('div', null,
            allowed('images.auto-optimize') ? el(Button, { variant: 'secondary', isBusy: busy === 'images.auto-optimize', disabled: !!busy, onClick: function () { run('images.auto-optimize'); }, style: { marginRight: 8, marginBottom: 8 } }, __('Optimize', 'davix-h2i')) : null,
            allowed('ai.content.alt-text') ? el(Button, { variant: 'secondary', isBusy: busy === 'ai.content.alt-text', disabled: !!busy, onClick: function () { run('ai.content.alt-text'); }, style: { marginBottom: 8 } }, __('Write alt text', 'davix-h2i')) : null,
            message ? el('p', null, message) : null);
    }

    function Card() {
        var postId = wp.data.useSelect(function (select) { return select('core/editor').getCurrentPostId(); }, []);
        var b = useState(false), busy = b[0], setBusy = b[1];
        var m = useState(''), message = m[0], setMessage = m[1];
        if (!allowed('render.html-to-image')) { return null; }
        return el('div', null,
            el(Button, {
                variant: 'secondary', isBusy: busy, disabled: busy, onClick: function () {
                    setBusy(true); setMessage('');
                    api('render/social-card', { post_id: postId })
                        .then(function (res) { setBusy(false); setMessage(res.ok ? __('Queued. The card will be added to your Media Library.', 'davix-h2i') : (res.message || __('Failed', 'davix-h2i'))); })
                        .catch(function (err) { setBusy(false); setMessage(err.message); });
                }
            }, __('Create a social card', 'davix-h2i')),
            message ? el('p', null, message) : null);
    }

    function Sidebar() {
        return el(wp.element.Fragment, null,
            el(PluginSidebarMoreMenuItem, { target: 'davix-h2i-sidebar' }, 'Davix H2I'),
            el(PluginSidebar, { name: 'davix-h2i-sidebar', title: 'Davix H2I', icon: 'images-alt2' },
                el(PanelBody, { title: __('Writing help', 'davix-h2i'), initialOpen: true }, el(Writing)),
                el(PanelBody, { title: __('Featured image', 'davix-h2i'), initialOpen: false }, el(Featured)),
                el(PanelBody, { title: __('Social card', 'davix-h2i'), initialOpen: false }, el(Card))));
    }

    wp.plugins.registerPlugin('davix-h2i', { render: Sidebar });
})(window.wp);
