/* The chip picker: pick a step from the select, it becomes a removable chip and shows its fields.
   Markup: .davix-h2i-chip-picker[data-chip-group] > select[data-chip-select] + .davix-h2i-chip-row[data-chip-row][data-input-name]
           and .davix-h2i-chip-fields-group[data-chip-group] > .davix-h2i-chip-fields[data-chip]. */
(function () {
    'use strict';

    function sync(group) {
        var row = document.querySelector('[data-chip-row="' + group + '"]');
        if (!row) { return; }
        var active = Dvx.qsa('.davix-h2i-chip', row).map(function (c) { return c.getAttribute('data-chip'); });
        Dvx.qsa('.davix-h2i-chip-fields-group[data-chip-group="' + group + '"] .davix-h2i-chip-fields').forEach(function (box) {
            var on = active.indexOf(box.getAttribute('data-chip')) > -1;
            box.style.display = on ? '' : 'none';
            Dvx.qsa('input, select, textarea', box).forEach(function (f) { f.disabled = !on; });
        });
        var select = document.querySelector('[data-chip-select="' + group + '"]');
        if (select) {
            Dvx.qsa('option', select).forEach(function (o) { o.hidden = o.value !== '' && active.indexOf(o.value) > -1; });
        }
    }

    function addChip(group, key, label) {
        var row = document.querySelector('[data-chip-row="' + group + '"]');
        if (!row || row.querySelector('[data-chip="' + key + '"]')) { return; }
        var name = row.getAttribute('data-input-name');
        var chip = document.createElement('span');
        chip.className = 'davix-h2i-chip';
        chip.setAttribute('data-chip', key);
        chip.innerHTML = '<span class="davix-h2i-chip-label">' + Dvx.esc(label) + '</span><button type="button" aria-label="Remove">&times;</button>' +
            (name ? '<input type="hidden" name="' + Dvx.esc(name) + '[]" value="' + Dvx.esc(key) + '" />' : '');
        row.appendChild(chip);
        sync(group);
    }

    Dvx.ready(function () {
        document.addEventListener('change', function (e) {
            var select = e.target.closest('[data-chip-select]');
            if (!select || !select.value) { return; }
            var opt = select.options[select.selectedIndex];
            addChip(select.getAttribute('data-chip-select'), select.value, opt.textContent);
            select.value = '';
        });
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.davix-h2i-chip button');
            if (!btn) { return; }
            var chip = btn.closest('.davix-h2i-chip');
            var row = chip.parentNode;
            chip.parentNode.removeChild(chip);
            sync(row.getAttribute('data-chip-row'));
        });
        Dvx.qsa('[data-chip-row]').forEach(function (row) { sync(row.getAttribute('data-chip-row')); });
    });

    window.DvxChips = { sync: sync, add: addChip };
})();
