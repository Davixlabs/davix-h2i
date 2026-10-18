/* Every dropdown of the plugin gets the same menu design as the Davix AI Engine (dropdown-kit.js). This marks the selects that
   exist now and the ones the screens add later (side panels, forms built on demand), then hands them to the kit. */
(function () {
    'use strict';
    var ROOTS = '.davix-admin, .davix-h2i-theme, .dvs-drawer, .dvx-side-drawer, #davix-h2i-modal, #davix-h2i-expand-modal';

    function mark(root) {
        if (!window.DavixDropdownKit || !root || root.nodeType !== 1) { return; }
        var list = root.matches && root.matches('select') ? [root] : Array.prototype.slice.call(root.querySelectorAll('select'));
        list.forEach(function (s) {
            if (s.multiple || s.size > 1 || s.hasAttribute('data-dvx-native') || s.classList.contains('dvs-select-native')) { return; }
            if (!s.closest(ROOTS)) { return; }
            s.classList.add('dvs-enhance-select');
            window.DavixDropdownKit.enhanceOne(s);
        });
    }

    // select.value = x from a script does not fire "change"; tell the menu so its label follows.
    var desc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
    if (desc && desc.set) {
        Object.defineProperty(HTMLSelectElement.prototype, 'value', {
            configurable: true,
            get: desc.get,
            set: function (v) {
                desc.set.call(this, v);
                if (this.dataset && this.dataset.davixEnhanced === '1') { this.dispatchEvent(new Event('dvx-sync')); }
            }
        });
    }

    function start() {
        mark(document.body);
        new MutationObserver(function (records) {
            records.forEach(function (r) { Array.prototype.forEach.call(r.addedNodes, mark); });
        }).observe(document.body, { childList: true, subtree: true });
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }
})();
