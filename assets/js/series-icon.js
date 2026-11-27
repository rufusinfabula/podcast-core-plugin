/**
 * Series icon picker (Podcast → Series, add and edit screens).
 */
( function ( $ ) {
	let frame;
	const l10n = window.podcastCoreSeriesIcon || {};

	$( document ).on( 'click', '.podcast-core-series-icon__choose', function ( e ) {
		e.preventDefault();
		const box = $( this ).closest( '.podcast-core-series-icon' );
		frame = wp.media( {
			title: l10n.title,
			button: { text: l10n.button },
			library: { type: 'image' },
			multiple: false,
		} );
		frame.on( 'select', function () {
			const att = frame.state().get( 'selection' ).first().toJSON();
			const src = ( att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail : att ).url;
			box.find( 'input[name="podcast_core_icon"]' ).val( att.id );
			box.find( '.podcast-core-series-icon__preview' ).html(
				$( '<img>', { src, alt: '', css: { width: 64, height: 64, objectFit: 'contain' } } )
			);
			box.find( '.podcast-core-series-icon__remove' ).prop( 'hidden', false );
		} );
		frame.open();
	} );

	$( document ).on( 'click', '.podcast-core-series-icon__remove', function ( e ) {
		e.preventDefault();
		const box = $( this ).closest( '.podcast-core-series-icon' );
		box.find( 'input[name="podcast_core_icon"]' ).val( '' );
		box.find( '.podcast-core-series-icon__preview' ).empty();
		$( this ).prop( 'hidden', true );
	} );

	// After "Add new series" (AJAX), clear the picker for the next one.
	$( document ).ajaxComplete( function ( event, xhr, settings ) {
		if ( settings.data && settings.data.indexOf( 'action=add-tag' ) !== -1 && xhr.responseXML && ! $( xhr.responseXML ).find( 'wp_error' ).length ) {
			$( '.podcast-core-series-icon__remove' ).trigger( 'click' );
		}
	} );
} )( jQuery );
