/**
 * Cookie notice: shows the banner until the visitor chooses; with consent, loads the videos kept
 * in <template> (see includes/class-consent.php). Links to #pc-cookie-settings reopen the banner.
 */
( function () {
	const KEY = 'pcConsent';
	const banner = document.querySelector( '.pc-consent' );

	function get() {
		try {
			return window.localStorage.getItem( KEY );
		} catch ( e ) {
			return null;
		}
	}
	function set( value ) {
		try {
			window.localStorage.setItem( KEY, value );
		} catch ( e ) {}
	}
	function load( box ) {
		const tpl = box.querySelector( 'template' );
		if ( tpl ) {
			box.replaceWith( tpl.content.cloneNode( true ) );
		}
	}
	function loadAll() {
		document.querySelectorAll( '.pc-consent-embed' ).forEach( load );
	}

	if ( 'yes' === get() ) {
		loadAll();
	} else if ( null === get() && banner ) {
		banner.hidden = false;
	}

	document.addEventListener( 'click', function ( e ) {
		const choice = e.target.closest( '[data-pc-consent]' );
		if ( choice ) {
			set( choice.dataset.pcConsent );
			banner.hidden = true;
			if ( 'yes' === choice.dataset.pcConsent ) {
				loadAll();
			}
			return;
		}
		const one = e.target.closest( '.pc-consent-embed__load' );
		if ( one ) {
			load( one.closest( '.pc-consent-embed' ) );
			return;
		}
		const settings = e.target.closest( 'a[href$="#pc-cookie-settings"]' );
		if ( settings && banner ) {
			e.preventDefault();
			banner.hidden = false;
			banner.querySelector( 'button' ).focus();
		}
	} );
} )();
