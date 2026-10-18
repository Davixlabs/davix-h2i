/**
 * DAVIX DROPDOWN KIT — shared, cross-plugin, dependency-free (vanilla JS).
 *
 * Vanilla-JS port of the behavior in davix-reply-admin.js's enhanceSelect()
 * (jQuery-based, Reply-only) — same visual design (davix-dropdown-kit.css),
 * same interaction model, deliberately DIFFERENT class names
 * (`dvs-select-*` instead of Reply's own `davix-ar-select-*`).
 *
 * That's not cosmetic: Reply's own script binds its open/close/select
 * handlers as DOCUMENT-level delegated jQuery listeners keyed by class name
 * (`$(document).on('click', '.davix-ar-select-trigger', ...)`), which match
 * ANY element with that class anywhere on the page — not just ones Reply's
 * own code created. Reusing those exact class names here caused Reply's
 * delegated handler and this kit's own per-element handler to both react to
 * the same click and immediately re-close what the other had just opened
 * (confirmed live). Separate class names avoid that collision entirely
 * while davix-dropdown-kit.css keeps both sets of classes styled
 * identically, so the two are visually indistinguishable.
 *
 * Written in vanilla JS (not jQuery) specifically so it can be called from
 * contexts that don't load jQuery — e.g. davix-ai-engine's own
 * settings/davix-admin.js, which is fetch()/vanilla already.
 *
 * Usage:
 *   DavixDropdownKit.enhance(document);        // sweep the whole page once
 *   DavixDropdownKit.enhance(someNewNode);      // sweep a newly-inserted subtree (AJAX, etc.)
 *
 * By default it enhances any `<select class="dvs-enhance-select">` found
 * under the given root. Pass an explicit selector as the second argument
 * to target something else.
 */
(function (window, document) {
  'use strict';

  if (window.DavixDropdownKit) { return; } // already loaded on this page

  var DEFAULT_SELECTOR = 'select.dvs-enhance-select';
  var ADD_NEW_SENTINEL = '__davix_add_new__';

  // Some credential selects are ALSO managed by the shared "Test
  // Connection" widget (davix-test-connection.js's arrangeRow()), which
  // physically MOVES the field into its own .dvs-connection-test__row
  // alongside a Test button and status circle. Confirmed live (original
  // bug): wrapping one of these before that move happened left the wrap's
  // trigger+menu behind as orphaned markup once arrangeRow() relocated the
  // bare <select> out of it — the trigger still rendered and could still
  // be clicked (it drives the same underlying <select> via closure
  // reference, so saves kept working), but it stopped reflecting later
  // changes to that select made by other scripts.
  //
  // These fields DO get the same pill/chevron treatment as every other
  // credential dropdown (confirmed: users expect Chatbot/Agent's "API
  // Credential" + Telegram Bot Token pickers to look identical to every
  // other Davix dropdown, Test button or not) — this guard only defers
  // that enhancement, it doesn't cancel it. arrangeRow() itself calls
  // DavixDropdownKit.enhance() immediately after physically moving the
  // field into its row (see davix-test-connection.js), and marks it with
  // data-davix-test-connection-arranged="1" right before doing so — so by
  // the time enhancement is allowed through, the field is already sitting
  // in its final DOM position and there's nothing left to orphan.
  // Skipped only pre-arrangement: the widget only records its target via a
  // `data-credential-selector` string at page load (the field isn't
  // inside the widget's DOM yet at that point) — checking that selector
  // directly, the same way Reply's own jQuery does, sidesteps the race
  // between this kit's own initial sweep and arrangeRow() entirely,
  // however early or late each runs.
  function isTestConnectionField(select) {
    if (select.getAttribute('data-davix-test-connection-arranged') === '1') {
      return false;
    }
    var widgets = document.querySelectorAll('[data-davix-test-connection]');
    for (var i = 0; i < widgets.length; i++) {
      var sel = widgets[i].getAttribute('data-credential-selector');
      if (sel) {
        try {
          if (select.matches(sel)) { return true; }
        } catch (e) { /* invalid selector string — ignore */ }
      }
    }
    return false;
  }

  function buildSelectMenu(select, menu) {
    menu.innerHTML = '';
    Array.prototype.forEach.call(select.querySelectorAll('option'), function (opt) {
      // Skip options another script hid via inline style/[hidden] — e.g.
      // updateCredentialDropdownsForProvider() in Engine's davix-admin.js
      // filters a credential <select>'s options down to the newly-selected
      // provider this way when the AI Provider changes. Confirmed live:
      // without this check, a provider switch left every credential from
      // every provider still listed here, even though the real <select>
      // had correctly hidden the non-matching ones.
      if (opt.style.display === 'none' || opt.hidden) { return; }
      var li = document.createElement('li');
      li.className = 'dvs-select-option';
      li.setAttribute('role', 'option');
      li.setAttribute('tabindex', '-1');
      li.textContent = opt.textContent;
      li.setAttribute('data-value', opt.value);
      li.setAttribute('aria-selected', opt.selected ? 'true' : 'false');
      if (opt.disabled) {
        li.classList.add('is-disabled');
        li.setAttribute('aria-disabled', 'true');
      }
      menu.appendChild(li);
    });
  }

  function syncSelectTrigger(select, trigger, menu) {
    var selected = select.options[select.selectedIndex];
    var label = trigger.querySelector('.dvs-select-trigger-label');
    label.textContent = selected ? selected.textContent : '';
    Array.prototype.forEach.call(menu.querySelectorAll('.dvs-select-option'), function (li) {
      li.setAttribute('aria-selected', li.getAttribute('data-value') === select.value ? 'true' : 'false');
    });
  }

  /* Davix H2I: the menu is moved to <body> while open, so no card, header or side panel can cover or clip it, and it is sized to
     the space that is really visible: below the trigger when it fits, otherwise above, otherwise the larger side with a scrollbar.
     The top edge never goes under the WordPress admin bar or the plugin's own sticky header. */
  function visibleTop() {
    var top = 0;
    Array.prototype.forEach.call(document.querySelectorAll('#wpadminbar, .davix-admin-header, .dvs-panel.dvs-panel-shell'), function (el) {
      var cs = window.getComputedStyle(el);
      if (cs.position !== 'fixed' && cs.position !== 'sticky') { return; }
      var r = el.getBoundingClientRect();
      if (r.height > 0 && r.top <= 1 && r.bottom > top) { top = r.bottom; }
    });
    return top;
  }

  function positionSelectMenu(trigger, menu) {
    var rect    = trigger.getBoundingClientRect();
    var margin  = 6;
    var top     = visibleTop() + margin;
    var count   = menu.children.length || 1;
    var wanted  = Math.min(280, count * 38 + 14);
    var below   = window.innerHeight - rect.bottom - margin * 2;
    var above   = rect.top - top - margin;
    var upward  = below < wanted && above > below;
    var room    = Math.max(96, Math.floor(upward ? above : below));

    menu.style.left = Math.max(4, Math.min(Math.round(rect.left), window.innerWidth - Math.round(rect.width) - 4)) + 'px';
    menu.style.width = Math.round(rect.width) + 'px';
    menu.style.setProperty('--dvs-select-menu-max-height', Math.min(280, room) + 'px');
    if (upward) {
      menu.style.top = 'auto';
      menu.style.bottom = Math.round(window.innerHeight - rect.top + margin) + 'px';
    } else {
      menu.style.bottom = 'auto';
      menu.style.top = Math.round(rect.bottom + margin) + 'px';
    }
  }

  function closeAllMenus(exceptMenu) {
    Array.prototype.forEach.call(document.querySelectorAll('.dvs-select-menu:not([hidden])'), function (m) {
      if (m !== exceptMenu) {
        m.setAttribute('hidden', '');
        var wrap = m._dvsWrap || m.closest('.dvs-select-wrap');
        var trigger = wrap && wrap.querySelector('.dvs-select-trigger');
        if (trigger) { trigger.setAttribute('aria-expanded', 'false'); }
      }
    });
  }

  function enhanceOne(select) {
    if (!select || select.tagName !== 'SELECT') { return; }
    if (select.dataset.davixEnhanced === '1') { return; }
    // Never wrap a genuine multi-select — this component's single-value
    // model (one visible trigger label, `select.value = ...` on choose)
    // would silently collapse a multi-select down to one selection. Some
    // callers only set `multiple` conditionally at render time (e.g. a
    // dynamic condition-value field whose `multiple`-ness depends on the
    // chosen condition type) and mark the select up front regardless —
    // this runtime check is what actually keeps those safe, not remembering
    // to leave the marker class off in every such template.
    if (select.multiple) { return; }
    // Excluded on purpose, not an oversight — see isTestConnectionField()
    // above: this specific field already has its own purpose-built,
    // Davix-styled treatment (button + status indicator) that a generic
    // dropdown wrapper isn't a good fit for anyway.
    if (isTestConnectionField(select)) { return; }
    select.dataset.davixEnhanced = '1';

    // Measure the native <select>'s own rendered display/width BEFORE
    // touching the DOM — the kit CSS previously forced every wrap to
    // `display:block; width:100%` unconditionally, which is correct for
    // fields that were already full-width (Reply's own settings rows) but
    // silently broke fields built for a compact, label-beside-control
    // layout (e.g. Chatbot/Agent's `.davix-pinned-row` flex rows,
    // `min-width:180px` selects meant to sit inline next to their label —
    // confirmed live: the forced 100% blew the control out to fill the
    // whole flex row and wrapped it onto its own line under
    // `flex-wrap:wrap`). Reproducing whatever the browser was already
    // about to render — instead of guessing at every plugin's own field
    // layout convention — is the only fix that's correct in every context
    // without special-casing each one.
    var nativeRect    = select.getBoundingClientRect();
    var nativeDisplay = window.getComputedStyle(select).display;
    // Was the select already filling (near enough) its containing block's
    // width, or genuinely compact/content-sized? Matters for what happens
    // on a later window resize: a field that was already fluid-100% should
    // stay fluid (`width:100%`, so it keeps tracking a resize the way the
    // native select would have), while a field that was never full-width
    // to begin with (e.g. `.davix-pinned-row`'s `min-width:180px` selects)
    // was never fluid either, so a fixed snapshot reproduces it correctly
    // without needing to re-measure a `<select>` this kit has since forced
    // to 1×1px and can't reliably re-measure later.
    var containingWidth = select.parentElement ? select.parentElement.getBoundingClientRect().width : 0;
    var wasFluid = containingWidth > 0 && nativeRect.width >= containingWidth - 2;

    var wrap = document.createElement('div');
    wrap.className = 'dvs-select-wrap';
    if (nativeRect.width > 0) {
      // A real, currently-visible measurement — reproduce it. !important
      // because the kit's own stylesheet default
      // (`.dvs-select-wrap { display:block; width:100% !important }`)
      // would otherwise win over a plain inline style at equal specificity.
      wrap.style.setProperty('display', (nativeDisplay === 'inline' ? 'inline-block' : nativeDisplay), 'important');
      wrap.style.setProperty('width', wasFluid ? '100%' : (Math.round(nativeRect.width) + 'px'), 'important');
    } else {
      // Select is inside a currently-hidden tab/panel (0×0 at sweep time)
      // — nothing real to measure yet. Default to compact/auto rather than
      // the old full-width default: an under-wide trigger the user can
      // still use is a far smaller problem than a wrongly-stretched one,
      // and this only affects fields that genuinely have no other sizing
      // signal available.
      wrap.style.setProperty('display', 'inline-block', 'important');
      wrap.style.setProperty('width', 'auto', 'important');
    }

    var trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'dvs-select-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.innerHTML = '<span class="dvs-select-trigger-label"></span>' +
      '<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>';

    var menu = document.createElement('ul');
    menu.className = 'dvs-select-menu';
    menu.setAttribute('role', 'listbox');
    menu.setAttribute('tabindex', '-1');
    menu.setAttribute('hidden', '');

    var parent = select.parentNode;
    parent.insertBefore(wrap, select);
    wrap.appendChild(trigger);
    wrap.appendChild(menu);
    wrap.appendChild(select);
    menu._dvsWrap = wrap;
    select.classList.add('dvs-select-native');

    buildSelectMenu(select, menu);
    syncSelectTrigger(select, trigger, menu);
    trigger.disabled = select.disabled;

    // Options can change after the fact (model list re-populating, a new
    // credential row appearing, etc.) — keep the custom menu in sync with
    // the real <select>'s children, not just at build time. Also watch the
    // `disabled` attribute: several Davix screens render a placeholder
    // <select disabled> ("No saved credentials available", etc.) and some
    // toggle disabled on/off at runtime once a prerequisite field is set —
    // the trigger button needs to track that or it'd stay clickable/styled
    // as enabled after the real control becomes disabled (or vice versa).
    var observer = new MutationObserver(function () {
      buildSelectMenu(select, menu);
      syncSelectTrigger(select, trigger, menu);
      trigger.disabled = select.disabled;
    });
    // subtree + style/hidden: also catches updateCredentialDropdownsForProvider()
    // (davix-admin.js) toggling individual <option> visibility in place, not
    // just options being added or removed outright (childList alone).
    observer.observe(select, { childList: true, subtree: true, attributes: true, attributeFilter: ['style', 'disabled', 'hidden'] });

    // The <select>'s value can also change from outside this widget
    // entirely — stay in sync either direction.
    select.addEventListener('change', function () {
      syncSelectTrigger(select, trigger, menu);
    });
    // Davix H2I: scripts that set select.value directly (no change event) announce it with this event.
    select.addEventListener('dvx-sync', function () {
      syncSelectTrigger(select, trigger, menu);
    });

    // A real, trusted browser "click" occasionally lands on the trigger a
    // few milliseconds after an option selection closes the menu (confirmed
    // live: mousedown/mouseup/click all target the option, then a SECOND,
    // separately-dispatched, isTrusted:true click targets the trigger --
    // reproducible with both a real mouse click and a scripted .click(),
    // timing consistent with the browser's own click hit-testing rather
    // than anything this code does). suppressReopenUntil swallows that one
    // phantom click so it can't re-open the menu the user just chose from
    // (user-reported: "when I click on [an option], choose it and close
    // the menu. Don't keep it open").
    var suppressReopenUntil = 0;
    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      if (Date.now() < suppressReopenUntil) { return; }
      var isOpen = !menu.hasAttribute('hidden');
      closeAllMenus(isOpen ? null : menu);
      if (isOpen) {
        menu.setAttribute('hidden', '');
        trigger.setAttribute('aria-expanded', 'false');
      } else {
        positionSelectMenu(trigger, menu);
        document.body.appendChild(menu);
        menu.removeAttribute('hidden');
        trigger.setAttribute('aria-expanded', 'true');
        var sel = menu.querySelector('[aria-selected="true"]') || menu.querySelector('.dvs-select-option');
        if (sel) { sel.focus(); }
      }
    });

    menu.addEventListener('click', function (e) {
      var option = e.target.closest('.dvs-select-option');
      if (!option || option.classList.contains('is-disabled')) { return; }
      var value = option.getAttribute('data-value');
      var isAddNewSentinel = (value === ADD_NEW_SENTINEL);
      select.value = value;
      // Native dispatchEvent, not a synthetic/jQuery trigger — this needs
      // to reach `document`-level capture-phase listeners other plugins'
      // scripts attach directly to the DOM (e.g. Engine's own credential
      // quick-add flow), which a non-native event dispatch can silently
      // miss depending on what else is listening.
      select.dispatchEvent(new Event('change', { bubbles: true }));
      syncSelectTrigger(select, trigger, menu);
      menu.setAttribute('hidden', '');
      trigger.setAttribute('aria-expanded', 'false');
      suppressReopenUntil = Date.now() + 350;
      // Don't steal focus back after the "+ Add new" sentinel — that
      // dispatchEvent may have synchronously opened another widget's modal
      // and moved focus into it; refocusing here would look like an
      // immediate dismiss to that modal.
      if (!isAddNewSentinel) { trigger.focus(); }
    });

    menu.addEventListener('keydown', function (e) {
      var option = e.target.closest('.dvs-select-option');
      if (!option) { return; }
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        option.click();
        return;
      }
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        var options = Array.prototype.slice.call(menu.querySelectorAll('.dvs-select-option'));
        var idx = options.indexOf(option);
        var next = (e.key === 'ArrowDown') ? idx + 1 : idx - 1;
        if (next < 0) { next = options.length - 1; }
        if (next >= options.length) { next = 0; }
        options[next].focus();
      }
    });
  }

  document.addEventListener('click', function (e) {
    if (e.target.closest('.dvs-select-wrap')) { return; }
    closeAllMenus(null);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { closeAllMenus(null); }
  });

  // Ported from davix-reply-admin.js's own scroll/resize handling — the
  // canonical dropdown does NOT track the trigger's position while
  // scrolling; it closes the menu outright on any scroll or resize. A
  // `position: fixed` menu computes its coordinates once, at open time, so
  // without this it stays pinned at those original viewport coordinates
  // while the trigger (and the rest of the page) scrolls away underneath
  // it — exactly the "menu floats away from its trigger" bug this closes
  // off by construction: a closed menu can never be visibly detached from
  // anything. `capture: true` on scroll because scroll events don't bubble
  // — this needs to fire for a scroll on ANY ancestor scroll container
  // (window, an `overflow:auto` settings card, a modal body, …), not just
  // window/document, and capture is the only phase that reaches all of
  // them from a single listener.
  //
  // LIVE-DISCOVERED BUG (Creator provider-aware dropdowns mission): the menu
  // itself is `overflow-y: auto` (davix-dropdown-kit.css) so a user
  // scrolling through its own option list also fires a native 'scroll'
  // event — and since scroll events don't bubble but DO propagate through
  // the capture phase on the way down to their real target, this
  // document-level capture listener saw that internal scroll too and
  // closed the menu the user was actively scrolling inside of, on the very
  // first wheel tick. With the menu gone, further wheel movement fell
  // through to whatever scrollable ancestor was now under the cursor (the
  // settings page body, or the wp-admin sidebar) — exactly the reported
  // "scrolling the dropdown scrolls the page/sidebar instead, and the
  // dropdown closes" bug. Fix: a scroll whose target is the open menu
  // itself (or something inside it) is the user legitimately scrolling the
  // option list and must NOT close it; only a scroll of some genuine
  // ANCESTOR (page, settings card, modal body, sidebar, …) still should,
  // since only that case risks the position:fixed menu visually detaching
  // from its trigger. `e.target.nodeType === 1` guards the window/document
  // scroll case, where e.target is the Document node (no `.closest()`).
  document.addEventListener('scroll', function (e) {
    var target = e.target;
    if (target && target.nodeType === 1 && target.closest('.dvs-select-menu')) {
      return;
    }
    closeAllMenus(null);
  }, true);
  window.addEventListener('resize', function () {
    closeAllMenus(null);
  });

  function enhance(root, selector) {
    root = root || document;
    selector = selector || DEFAULT_SELECTOR;
    var nodes = (root.nodeType === 1 && root.matches && root.matches(selector))
      ? [root]
      : root.querySelectorAll(selector);
    Array.prototype.forEach.call(nodes, enhanceOne);
  }

  window.DavixDropdownKit = {
    DEFAULT_SELECTOR: DEFAULT_SELECTOR,
    enhanceOne: enhanceOne,
    enhance: enhance
  };

  // One-time sweep of whatever's already in the page at load — covers
  // every marked <select> rendered server-side on first paint. Callers
  // only need to invoke DavixDropdownKit.enhance(root) themselves for
  // markup inserted AFTER this point (AJAX responses, cloned templates).
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { enhance(document); });
  } else {
    enhance(document);
  }
})(window, document);
