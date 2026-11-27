( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps } = wp.blockEditor;
	const { createElement: el } = wp.element;
	const ServerSideRender = wp.serverSideRender;
	const { __ } = wp.i18n;

	registerBlockType( 'podcast-core/episode-video', {
		edit( { attributes, context } ) {
			return el(
				'div',
				useBlockProps(),
				el( ServerSideRender, {
					block: 'podcast-core/episode-video',
					attributes,
					urlQueryArgs: context.postId ? { post_id: context.postId } : {},
					EmptyResponsePlaceholder: () =>
						el( 'p', { className: 'pc-player__placeholder' }, __( 'Episode video: shown here when the episode has a Video URL.', 'podcast-core-engine' ) ),
				} )
			);
		},
		save: () => null,
	} );
} )( window.wp );
