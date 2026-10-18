/* The full-screen editor for long text fields ([data-dvx-expand="#textarea-id"]). */
(function () {
    'use strict';
    var modal, textarea, target;

    function build() {
        modal = document.createElement('div');
        modal.className = 'dvs-fullscreen-editor davix-admin davix-h2i-theme';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.hidden = true;
        modal.innerHTML =
            '<div class="dvs-fullscreen-editor-header"><div class="dvs-fullscreen-editor-title-wrap"><h2 class="dvs-fullscreen-editor-title"></h2></div>' +
            '<button type="button" class="dvs-fullscreen-editor-close" data-close aria-label="Close">&times;</button></div>' +
            '<div class="dvs-fullscreen-editor-body"><textarea class="dvs-fullscreen-editor-textarea" spellcheck="false"></textarea></div>' +
            '<div class="dvs-fullscreen-editor-footer"><button type="button" class="dvs-save-button" data-save>Save</button><button type="button" class="dvs-btn" data-close>Cancel</button></div>';
        document.body.appendChild(modal);
        textarea = modal.querySelector('textarea');
        modal.addEventListener('click', function (e) {
            if (e.target.closest('[data-save]')) { target.value = textarea.value; target.dispatchEvent(new Event('input', { bubbles: true })); close(); }
            else if (e.target.closest('[data-close]')) { close(); }
        });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) { close(); } });
    }

    function close() { modal.hidden = true; document.body.classList.remove('dvx-modal-open'); }

    Dvx.ready(function () {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-dvx-expand]');
            if (!btn) { return; }
            if (!modal) { build(); }
            target = document.querySelector(btn.getAttribute('data-dvx-expand'));
            if (!target) { return; }
            modal.querySelector('.dvs-fullscreen-editor-title').textContent = btn.getAttribute('data-expand-title') || 'Editor';
            textarea.value = target.value;
            modal.hidden = false;
            textarea.focus();
        });
    });
})();
