( function () {
	const { __, sprintf } = wp.i18n;
	const out = document.getElementById( 'pc-import-output' );
	const bar = document.getElementById( 'pc-import-progress' );
	const previewBtn = document.getElementById( 'pc-import-preview' );
	const runBtn = document.getElementById( 'pc-import-run' );
	if ( ! out || ! runBtn ) {
		return;
	}
	const say = ( html ) => ( out.innerHTML = '<p>' + html + '</p>' );
	const esc = ( s ) => String( s ).replace( /[&<>"]/g, ( c ) => ( { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ c ] ) );
	const busy = ( on ) => {
		previewBtn.disabled = on;
		runBtn.disabled = on;
	};
	const fail = ( e ) => {
		busy( false );
		bar.style.display = 'none';
		say( '<strong>' + esc( e.message || __( 'Something went wrong.', 'podcast-core-plugin' ) ) + '</strong>' );
	};

	previewBtn.addEventListener( 'click', async () => {
		busy( true );
		say( __( 'Reading the feed…', 'podcast-core-plugin' ) );
		try {
			const p = await wp.apiFetch( { path: '/podcast-core/v1/import/preview' } );
			// translators: 1: episodes in the feed, 2: new episodes, 3: episodes already on the site.
			let html = sprintf( __( '%1$d episodes in the feed: %2$d new, %3$d already on the site.', 'podcast-core-plugin' ), p.total, p.new, p.existing );
			if ( p.sample.length ) {
				html += '</p><ul style="list-style:disc;margin-left:2em">' + p.sample.map( ( t ) => '<li>' + esc( t ) + '</li>' ).join( '' ) + ( p.new > p.sample.length ? '<li>…</li>' : '' ) + '</ul><p>';
			}
			say( html );
		} catch ( e ) {
			fail( e );
			return;
		}
		busy( false );
	} );

	runBtn.addEventListener( 'click', async () => {
		busy( true );
		let created = 0;
		let errors = [];
		let total = null;
		bar.style.display = 'block';
		bar.value = 0;
		say( __( 'Importing… keep this page open.', 'podcast-core-plugin' ) );
		try {
			// Each request imports a small batch (images are downloaded), until nothing is left.
			for ( ;; ) {
				const r = await wp.apiFetch( { path: '/podcast-core/v1/import/run', method: 'POST' } );
				created += r.created;
				errors = errors.concat( r.errors );
				if ( total === null ) {
					total = r.created + r.pending;
				}
				bar.value = total ? Math.round( ( created / total ) * 100 ) : 100;
				// translators: 1: episodes imported so far, 2: new episodes to import.
				say( sprintf( __( 'Imported %1$d of %2$d new episodes…', 'podcast-core-plugin' ), created, total ) );
				if ( ! r.pending || ! r.created ) {
					break;
				}
			}
		} catch ( e ) {
			fail( e );
			return;
		}
		bar.value = 100;
		busy( false );
		let html = created
			? // translators: %d: number of imported episodes.
			  sprintf( __( 'Done: %d new episodes imported.', 'podcast-core-plugin' ), created )
			: __( 'Everything is up to date: no new episodes.', 'podcast-core-plugin' );
		if ( errors.length ) {
			html += '</p><p><strong>' + __( 'Problems:', 'podcast-core-plugin' ) + '</strong></p><ul style="list-style:disc;margin-left:2em">' + errors.map( ( e ) => '<li>' + esc( e ) + '</li>' ).join( '' ) + '</ul><p>';
		}
		say( html );
		document.getElementById( 'pc-import-last' ).textContent = __( 'Just now', 'podcast-core-plugin' );
	} );
} )();
