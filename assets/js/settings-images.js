/**
 * Image list field (Podcast → Settings → Styles → Hero images): choose several images from the Media
 * Library, drag to reorder, remove all.
 */
( function ( $ ) {
	const l10n = window.podcastCoreImages || {};

	function sync( box ) {
		const ids = box.find( '.podcast-core-images__list li' ).map( function () {
			return $( this ).data( 'id' );
		} ).get();
		box.find( 'input[type="hidden"]' ).val( ids.join( ',' ) );
		box.find( '.podcast-core-images__clear' ).prop( 'hidden', ! ids.length );
	}

	$( '.podcast-core-images__list' ).sortable( {
		update() {
			sync( $( this ).closest( '.podcast-core-images' ) );
		},
	} );

	$( document ).on( 'click', '.podcast-core-images__choose', function ( e ) {
		e.preventDefault();
		const box = $( this ).closest( '.podcast-core-images' );
		const frame = wp.media( {
			title: l10n.title,
			button: { text: l10n.button },
			library: { type: 'image' },
			multiple: 'add',
		} );
		frame.on( 'open', function () {
			const selection = frame.state().get( 'selection' );
			box.find( '.podcast-core-images__list li' ).each( function () {
				const att = wp.media.attachment( $( this ).data( 'id' ) );
				att.fetch();
				selection.add( att );
			} );
		} );
		frame.on( 'select', function () {
			const list = box.find( '.podcast-core-images__list' ).empty();
			frame.state().get( 'selection' ).each( function ( att ) {
				const a = att.toJSON();
				const src = ( a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail : a ).url;
				list.append( $( '<li>' ).attr( 'data-id', a.id ).append( $( '<img>', { src, alt: '' } ) ) );
			} );
			sync( box );
		} );
		frame.open();
	} );

	$( document ).on( 'click', '.podcast-core-images__clear', function ( e ) {
		e.preventDefault();
		const box = $( this ).closest( '.podcast-core-images' );
		box.find( '.podcast-core-images__list' ).empty();
		sync( box );
	} );
} )( jQuery );
