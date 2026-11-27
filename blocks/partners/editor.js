( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, ToggleControl, RangeControl, SelectControl } = wp.components;
	const { useSelect } = wp.data;
	const { createElement: el, Fragment } = wp.element;
	const ServerSideRender = wp.serverSideRender;
	const { __ } = wp.i18n;

	registerBlockType( 'podcast-core/partners', {
		edit( { attributes, setAttributes } ) {
			const blockProps = useBlockProps();
			const types = useSelect(
				( select ) =>
					select( 'core' ).getEntityRecords( 'taxonomy', 'podcast_partner_type', { per_page: 100, hide_empty: false } ) || [],
				[]
			);
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
						{ title: __( 'Partners', 'podcast-core-engine' ) },
						el( SelectControl, {
							label: __( 'Type', 'podcast-core-engine' ),
							value: attributes.type,
							options: [ { label: __( 'All types', 'podcast-core-engine' ), value: '' } ].concat(
								types.map( ( t ) => ( { label: t.name, value: t.slug } ) )
							),
							onChange: ( v ) => setAttributes( { type: v } ),
							help: __( 'Manage partners and types in Podcast → Partners.', 'podcast-core-engine' ),
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
						} ),
						el( RangeControl, {
							label: __( 'Columns', 'podcast-core-engine' ),
							value: attributes.columns,
							min: 1,
							max: 6,
							onChange: ( v ) => setAttributes( { columns: v } ),
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
						} ),
						el( RangeControl, {
							label: __( 'Maximum number (0 = all)', 'podcast-core-engine' ),
							value: attributes.limit,
							min: 0,
							max: 48,
							onChange: ( v ) => setAttributes( { limit: v } ),
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
						} ),
						toggle( 'showName', __( 'Show name', 'podcast-core-engine' ) ),
						toggle( 'showText', __( 'Show short text', 'podcast-core-engine' ) )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'podcast-core/partners',
						attributes,
						EmptyResponsePlaceholder: () =>
							el(
								'p',
								null,
								__( 'Partners: add them in Podcast → Partners to see them here.', 'podcast-core-engine' )
							),
					} )
				)
			);
		},
		save: () => null,
	} );
} )( window.wp );
