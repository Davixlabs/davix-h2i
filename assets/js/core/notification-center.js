/* Notification capsule: collects WordPress admin notices (moved, not cloned, so their buttons keep working) and the plugin's own
   messages into a bottom-right pill with a small drawer. Gated on the .davix-admin shell. */
( function () {
	'use strict';

	// Messages sent before the capsule is built wait here.
	var queued = [];
	window.DavixNC = { push: function ( message, type ) { queued.push( [ message, type ] ); } };

	function boot() {
		var screen = document.querySelector( '#davix-h2i-admin' );
		if ( ! screen || document.querySelector( '.davix-nc' ) ) {
			return;
		}

		var wrapScreen = screen.closest( '.wrap.davix-h2i-settings-screen' ) || document.querySelector( '.wrap.davix-h2i-settings-screen' );
		var tray = document.querySelector( '.davix-admin-notice-tray' );
		var wpBody = document.getElementById( 'wpbody-content' );

		var cfg = window.DavixH2I || {};
		var i18n = cfg.i18n || {};

		function t( key, fallback ) {
			return i18n[ key ] || fallback;
		}

		function esc( str ) {
			var d = document.createElement( 'div' );
			d.textContent = String( str == null ? '' : str );
			return d.innerHTML;
		}

		/* ── Build trigger + panel shell ─────────────────────────── */
		var root = document.createElement( 'div' );
		root.className = 'davix-nc';
		root.innerHTML =
			'<button type="button" class="davix-nc-trigger" id="davix-nc-trigger" aria-haspopup="dialog" aria-expanded="false" aria-controls="davix-nc-panel" aria-label="' + esc( t( 'trigger', 'Notifications' ) ) + '" hidden>' +
				'<span class="dashicons dashicons-bell" aria-hidden="true"></span>' +
				'<span class="davix-nc-trigger-count" id="davix-nc-trigger-count">0</span>' +
			'</button>' +
			'<section class="davix-nc-panel" id="davix-nc-panel" role="dialog" aria-modal="false" aria-label="' + esc( t( 'title', 'Notifications' ) ) + '" hidden>' +
				'<div class="davix-nc-list" id="davix-nc-list" hidden></div>' +
				'<div class="davix-nc-empty" id="davix-nc-empty" hidden>' +
					'<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>' +
					'<p>' + esc( t( 'emptyTitle', "You're all caught up" ) ) + '</p>' +
					'<p class="davix-nc-empty-sub">' + esc( t( 'emptySub', 'There are no admin notifications right now.' ) ) + '</p>' +
				'</div>' +
			'</section>';

		document.body.appendChild( root );

		var trigger = root.querySelector( '#davix-nc-trigger' );
		var triggerCount = root.querySelector( '#davix-nc-trigger-count' );
		var panel = root.querySelector( '#davix-nc-panel' );
		var list = root.querySelector( '#davix-nc-list' );
		var empty = root.querySelector( '#davix-nc-empty' );

		var state = {
			items: [],
			nextId: 1,
			open: false,
			initialSweepDone: false,
		};
		var seen = new WeakSet();

		/* ── Severity + preview helpers ──────────────────────────── */
		function classify( node ) {
			var cl = node.classList;
			if ( cl.contains( 'notice-error' ) || cl.contains( 'error' ) ) {
				return 'error';
			}
			if ( cl.contains( 'notice-warning' ) || cl.contains( 'update-nag' ) ) {
				return 'warning';
			}
			if ( cl.contains( 'notice-success' ) || cl.contains( 'updated' ) ) {
				return 'success';
			}
			return 'info';
		}

		var ICONS = {
			info: 'dashicons-info',
			success: 'dashicons-yes-alt',
			warning: 'dashicons-warning',
			error: 'dashicons-dismiss',
		};

		function previewText( node ) {
			var clone = node.cloneNode( true );
			var kill = clone.querySelectorAll( 'script, style, .notice-dismiss' );
			for ( var i = 0; i < kill.length; i++ ) {
				kill[ i ].parentNode.removeChild( kill[ i ] );
			}
			var txt = ( clone.textContent || '' ).replace( /\s+/g, ' ' ).trim();
			if ( ! txt ) {
				return t( 'noPreview', 'Notification' );
			}
			return txt.length > 160 ? txt.slice( 0, 157 ) + '…' : txt;
		}

		/* ── Eligibility / collection ────────────────────────────── */
		function collectIfEligible( node ) {
			if ( seen.has( node ) || node.closest( '.davix-nc' ) ) {
				return;
			}
			if ( node.hasAttribute( 'data-davix-notice-inline' ) ) {
				return;
			}

			var isNamedNotice = node.classList.contains( 'notice' ) || node.classList.contains( 'update-nag' );
			var isLegacy = node.classList.contains( 'updated' ) || node.classList.contains( 'error' );
			if ( ! isNamedNotice && ! isLegacy ) {
				return;
			}

			// Legacy `.updated`/`.error` markers (no `.notice` class) are common
			// on unrelated UI (e.g. form-validation error boxes) — only trust
			// them as real admin notices when they sit at the top level of a
			// known notice-hosting root, never when buried in arbitrary content.
			if ( ! isNamedNotice && isLegacy ) {
				var parent = node.parentElement;
				if ( parent !== tray && parent !== wpBody && parent !== wrapScreen ) {
					return;
				}
			}

			// Defensive: ignore notices that belong to a different
			// `.wrap.davix-settings-screen` instance than this one.
			var owningScreen = node.closest( '.wrap.davix-h2i-settings-screen' );
			if ( owningScreen && wrapScreen && owningScreen !== wrapScreen ) {
				return;
			}

			seen.add( node );
			addCard( node );
		}

		function sweep() {
			var scopes = [];
			if ( tray ) {
				scopes.push( tray );
			}
			if ( wpBody ) {
				scopes.push( wpBody );
			}
			if ( wrapScreen ) {
				scopes.push( wrapScreen );
			}
			scopes.forEach( function ( rootEl ) {
				var candidates = rootEl.querySelectorAll( '.notice, .update-nag, .updated, .error' );
				candidates.forEach( function ( node ) {
					collectIfEligible( node );
				} );
			} );
			state.initialSweepDone = true;
		}

		/* ── Card building ────────────────────────────────────────── */
		function buildCard( id, node, severity ) {
			var card = document.createElement( 'article' );
			card.className = 'davix-nc-card is-' + severity;
			card.dataset.ncId = id;

			var top = document.createElement( 'div' );
			top.className = 'davix-nc-card-top';

			var icon = document.createElement( 'span' );
			icon.className = 'davix-nc-card-icon dashicons ' + ( ICONS[ severity ] || ICONS.info );
			icon.setAttribute( 'aria-hidden', 'true' );

			var copy = document.createElement( 'div' );
			copy.className = 'davix-nc-card-copy';
			var previewEl = document.createElement( 'p' );
			previewEl.className = 'davix-nc-card-preview';
			previewEl.textContent = previewText( node );
			copy.appendChild( previewEl );

			top.appendChild( icon );
			top.appendChild( copy );

			// Every card gets a close control in the same spot, regardless of
			// whether WordPress marked the underlying notice `.is-dismissible`
			// — dismissItem() below already falls back to just removing the
			// card/node from view when there's no real `.notice-dismiss` to
			// delegate to, so this is always safe to show.
			var dismissBtn = document.createElement( 'button' );
			dismissBtn.type = 'button';
			dismissBtn.className = 'davix-nc-card-dismiss';
			dismissBtn.setAttribute( 'aria-label', t( 'dismiss', 'Dismiss' ) );
			dismissBtn.innerHTML = '&times;';
			dismissBtn.addEventListener( 'click', function () {
				dismissItem( id );
			} );
			top.appendChild( dismissBtn );

			card.appendChild( top );

			var toggle = document.createElement( 'button' );
			toggle.type = 'button';
			toggle.className = 'davix-nc-card-toggle';
			toggle.setAttribute( 'aria-expanded', 'false' );
			toggle.textContent = t( 'showDetails', 'Show details' );

			var body = document.createElement( 'div' );
			body.className = 'davix-nc-card-body';
			body.hidden = true;
			body.appendChild( node );

			toggle.addEventListener( 'click', function () {
				var isHidden = body.hidden;
				body.hidden = ! isHidden;
				toggle.setAttribute( 'aria-expanded', isHidden ? 'true' : 'false' );
				toggle.textContent = isHidden ? t( 'hideDetails', 'Hide details' ) : t( 'showDetails', 'Show details' );
			} );

			card.appendChild( toggle );
			card.appendChild( body );

			// Other admin-notice tooling on the page (e.g. Davix AI Engine's
			// shared notice-relocation script, which runs globally on every
			// wp-admin screen and isn't aware this notice already has a home)
			// can grab an already-claimed node right back out of the card for
			// up to ~20s after load. Treat "node left the card but is still
			// attached to the document somewhere" as a theft and reclaim it,
			// rather than treating it as a real dismissal. Only a node that's
			// genuinely gone from the document (removed outright, e.g. by our
			// own dismiss handler below or a plugin's own AJAX dismissal)
			// counts as dismissed.
			var bodyObserver = new MutationObserver( function () {
				if ( body.contains( node ) ) {
					return;
				}
				if ( node.isConnected ) {
					body.appendChild( node );
					return;
				}
				bodyObserver.disconnect();
				removeItem( id );
			} );
			bodyObserver.observe( body, { childList: true } );

			return card;
		}

		function addCard( node ) {
			var id = 'nc-' + state.nextId++;
			var severity = classify( node );
			var isDynamic = state.initialSweepDone;

			var card = buildCard( id, node, severity );
			if ( isDynamic ) {
				card.classList.add( 'is-new' );
				list.insertBefore( card, list.firstChild );
			} else {
				list.appendChild( card );
			}

			state.items.push( { id: id, node: node, card: card } );

			refreshChrome();
		}

		function findItem( id ) {
			for ( var i = 0; i < state.items.length; i++ ) {
				if ( state.items[ i ].id === id ) {
					return state.items[ i ];
				}
			}
			return null;
		}

		function removeItem( id ) {
			var idx = -1;
			for ( var i = 0; i < state.items.length; i++ ) {
				if ( state.items[ i ].id === id ) {
					idx = i;
					break;
				}
			}
			if ( idx === -1 ) {
				return;
			}
			var item = state.items[ idx ];
			if ( item.card.parentNode ) {
				item.card.parentNode.removeChild( item.card );
			}
			state.items.splice( idx, 1 );
			refreshChrome();
		}

		function dismissItem( id ) {
			var item = findItem( id );
			if ( ! item ) {
				return;
			}
			var realDismiss = item.node.querySelector( '.notice-dismiss' );
			if ( realDismiss ) {
				// Fires WordPress core's delegated dismiss handler (and any
				// plugin-specific AJAX persistence bound the same way); the
				// MutationObserver on the card body detects the resulting
				// removal (node.isConnected becomes false) and cleans up the
				// card for us.
				realDismiss.click();
			} else {
				if ( item.node.parentNode ) {
					item.node.parentNode.removeChild( item.node );
				}
				removeItem( id );
			}
		}

		function syncTriggerHeight() {
			if ( trigger.hidden ) {
				return;
			}
			var h = trigger.getBoundingClientRect().height;
			if ( h > 0 ) {
				document.documentElement.style.setProperty( '--dvs-nc-trigger-h', h + 'px' );
			}
		}

		function refreshChrome() {
			var total = state.items.length;
			var hasItems = total > 0;

			list.hidden = ! hasItems;
			empty.hidden = hasItems;

			// The capsule is only ever shown while there's something to show —
			// hidden outright (not just faded) the moment the list empties —
			trigger.hidden = ! hasItems;
			triggerCount.textContent = hasItems
				? ( total + ' ' + ( 1 === total
					? t( 'newNotificationSingular', 'new notification' )
					: t( 'newNotificationPlural', 'new notifications' ) ) )
				: '';

			syncTriggerHeight();

			// With no trigger left to click and no visible close button on the
			// panel itself, an open-but-now-empty drawer would otherwise be
			// stuck on screen with no obvious way to dismiss it — close it the
			// moment the last notification is cleared.
			if ( ! hasItems && state.open ) {
				closePanel();
			}
		}

		function clearNewHighlight() {
			state.items.forEach( function ( item ) {
				item.card.classList.remove( 'is-new' );
			} );
		}

		/* ── Open / close ─────────────────────────────────────────── */
		function onKeydown( event ) {
			if ( event.key === 'Escape' ) {
				closePanel();
			}
		}

		function onOutsideClick( event ) {
			if ( ! root.contains( event.target ) ) {
				closePanel();
			}
		}

		function openPanel() {
			if ( state.open ) {
				return;
			}
			panel.hidden = false;
			// Next frame so the opacity/transform transition actually runs.
			window.requestAnimationFrame( function () {
				root.classList.add( 'is-open' );
			} );
			trigger.setAttribute( 'aria-expanded', 'true' );
			state.open = true;
			clearNewHighlight();
			document.addEventListener( 'keydown', onKeydown, true );
			document.addEventListener( 'mousedown', onOutsideClick, true );
		}

		function closePanel() {
			if ( ! state.open ) {
				return;
			}
			root.classList.remove( 'is-open' );
			trigger.setAttribute( 'aria-expanded', 'false' );
			state.open = false;
			document.removeEventListener( 'keydown', onKeydown, true );
			document.removeEventListener( 'mousedown', onOutsideClick, true );
			setTimeout( function () {
				if ( ! state.open ) {
					panel.hidden = true;
				}
			}, 200 );
			trigger.focus();
		}

		trigger.addEventListener( 'click', function () {
			if ( state.open ) {
				closePanel();
			} else {
				openPanel();
			}
		} );

		// The capsule's stretched text can wrap differently at narrower
		// widths, which changes its rendered height — keep the panel's anchor (both driven by
		// --davix-nc-trigger-h) in sync with that.
		var resizeTimer;
		window.addEventListener( 'resize', function () {
			clearTimeout( resizeTimer );
			resizeTimer = setTimeout( syncTriggerHeight, 100 );
		} );

		/* ── The plugin's own messages (dvx.toast) land here on plugin screens ── */
		function push( message, type ) {
			var kind = 'error' === type ? 'error' : ( 'success' === type ? 'success' : ( 'warning' === type ? 'warning' : 'info' ) );
			var node = document.createElement( 'div' );
			node.className = 'notice notice-' + kind;
			var p = document.createElement( 'p' );
			p.textContent = String( message == null ? '' : message );
			node.appendChild( p );
			seen.add( node );
			addCard( node );
			var item = state.items[ state.items.length - 1 ];
			if ( item && 'error' !== kind && 'warning' !== kind ) {
				setTimeout( function () { dismissItem( item.id ); }, 15000 );
			}
		}
		window.DavixNC = { push: push };
		( queued || [] ).forEach( function ( q ) { push( q[ 0 ], q[ 1 ] ); } );

		/* ── Initial sweep + live collection ─────────────────────── */
		sweep();
		refreshChrome();

		if ( wpBody ) {
			var sweepTimer;
			var domObserver = new MutationObserver( function () {
				clearTimeout( sweepTimer );
				sweepTimer = setTimeout( sweep, 30 );
			} );
			domObserver.observe( wpBody, { childList: true, subtree: true } );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
