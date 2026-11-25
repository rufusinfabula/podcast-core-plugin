( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, ToggleControl } = wp.components;
	const { createElement: el, Fragment } = wp.element;
	const ServerSideRender = wp.serverSideRender;
	const { __ } = wp.i18n;

	registerBlockType( 'podcast-core/episode-player', {
		edit( { attributes, setAttributes, context } ) {
			const blockProps = useBlockProps();
			const toggle = ( key, label ) =>
				el( ToggleControl, {
					label,
					checked: attributes[ key ],
					onChange: ( v ) => setAttributes( { [ key ]: v } ),
					__nextHasNoMarginBottom: true,
				} );

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Display', 'podcast-core-plugin' ) },
						toggle( 'showCover', __( 'Show cover image', 'podcast-core-plugin' ) ),
						toggle( 'showNumber', __( 'Show episode number', 'podcast-core-plugin' ) ),
						toggle( 'showVideo', __( 'Show video', 'podcast-core-plugin' ) )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'podcast-core/episode-player',
						attributes,
						urlQueryArgs: context.postId ? { post_id: context.postId } : {},
						EmptyResponsePlaceholder: () =>
							el(
								'p',
								{ className: 'pc-player__placeholder' },
								__( 'Episode player: set an audio URL in "Episode details" to see it here.', 'podcast-core-plugin' )
							),
					} )
				)
			);
		},
		save: () => null,
	} );
} )( window.wp );
