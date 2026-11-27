( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, ToggleControl, RangeControl, SelectControl } = wp.components;
	const { useSelect } = wp.data;
	const { createElement: el, Fragment } = wp.element;
	const ServerSideRender = wp.serverSideRender;
	const { __ } = wp.i18n;

	registerBlockType( 'podcast-core/series-list', {
		edit( { attributes, setAttributes } ) {
			const blockProps = useBlockProps();
			const series = useSelect(
				( select ) =>
					select( 'core' ).getEntityRecords( 'taxonomy', 'podcast_series', { per_page: 100, hide_empty: false } ) || [],
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
						{ title: __( 'Series', 'podcast-core-engine' ) },
						el( SelectControl, {
							label: __( 'Series to show', 'podcast-core-engine' ),
							value: attributes.parent,
							options: [ { label: __( 'Main series', 'podcast-core-engine' ), value: '' } ].concat(
								series.map( ( t ) => ( {
									/* translators: %s: series name */
									label: __( 'Sub-series of %s', 'podcast-core-engine' ).replace( '%s', t.name ),
									value: t.slug,
								} ) )
							),
							onChange: ( v ) => setAttributes( { parent: v } ),
							help: __( 'Manage series, sub-series and their icons in Podcast → Series.', 'podcast-core-engine' ),
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
						el( SelectControl, {
							label: __( 'Order', 'podcast-core-engine' ),
							value: attributes.orderBy,
							options: [
								{ label: __( 'By name', 'podcast-core-engine' ), value: 'name' },
								{ label: __( 'Most recent episode first', 'podcast-core-engine' ), value: 'latest' },
							],
							onChange: ( v ) => setAttributes( { orderBy: v } ),
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
						} ),
						el( RangeControl, {
							label: __( 'Latest episodes under each series', 'podcast-core-engine' ),
							help: __( 'Their images, under the series name. The whole card links to the series. 0 = none.', 'podcast-core-engine' ),
							value: attributes.episodes,
							min: 0,
							max: 12,
							onChange: ( v ) => setAttributes( { episodes: v } ),
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
						} ),
						toggle( 'showIcon', __( 'Show icon', 'podcast-core-engine' ) ),
						toggle( 'showCount', __( 'Show number of episodes', 'podcast-core-engine' ) ),
						toggle( 'showDescription', __( 'Show description', 'podcast-core-engine' ) ),
						toggle( 'hideEmpty', __( 'Hide series without episodes', 'podcast-core-engine' ) )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'podcast-core/series-list',
						attributes,
						EmptyResponsePlaceholder: () =>
							el( 'p', null, __( 'Series list: no series to show yet.', 'podcast-core-engine' ) ),
					} )
				)
			);
		},
		save: () => null,
	} );
} )( window.wp );
