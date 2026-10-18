/* Images tab: nothing to add beyond the shared chip picker and run panel. Kept as the module's own entry point. */
(function () {
    'use strict';
    Dvx.ready(function () {
        if (window.DvxChips) {
            Dvx.qsa('[data-chip-row]').forEach(function (row) { window.DvxChips.sync(row.getAttribute('data-chip-row')); });
        }
    });
})();
