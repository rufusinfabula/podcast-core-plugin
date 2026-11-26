/**
 * "Sections" panel in the Site Editor: one switch per section of the template's
 * main area. Switching off hides the block on the site (native block visibility,
 * metadata.blockVisibility = false) without deleting it.
 */
( function ( wp ) {
	const { registerPlugin } = wp.plugins;
	const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editor;
	const { PanelBody, ToggleControl, Button, Flex, FlexItem } = wp.components;
	const { useSelect, useDispatch } = wp.data;
	const { getBlockType } = wp.blocks;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;

	const label = ( block ) =>
		block.attributes?.metadata?.name ||
		( getBlockType( block.name ) || {} ).title ||
		block.name;

	// Sections = the blocks of the edited page (inside Post Content when the template
	// is shown), otherwise the children of the template's <main> group, otherwise the
	// top-level blocks that are not template parts.
	const findSections = ( select ) => {
		const editor = select( 'core/block-editor' );
		const blocks = editor.getBlocks();
		const walk = ( list ) => {
			for ( const b of list ) {
				if ( b.name === 'core/post-content' ) {
					return b;
				}
				const found = walk( editor.getBlocks( b.clientId ) );
				if ( found ) {
					return found;
				}
			}
			return null;
		};
		const postContent = walk( blocks );
		if ( postContent ) {
			return editor.getBlocks( postContent.clientId );
		}
		const main = blocks.find( ( b ) => b.attributes?.tagName === 'main' );
		if ( main ) {
			return main.innerBlocks;
		}
		return blocks.filter( ( b ) => b.name !== 'core/template-part' );
	};

	function SectionsPanel() {
		const sections = useSelect( ( select ) => findSections( select ), [] );
		const { updateBlockAttributes, selectBlock } = useDispatch( 'core/block-editor' );

		const setVisible = ( block, visible ) => {
			const metadata = { ...( block.attributes.metadata || {} ) };
			if ( visible ) {
				delete metadata.blockVisibility;
			} else {
				metadata.blockVisibility = false;
			}
			updateBlockAttributes( block.clientId, { metadata } );
		};

		const content = sections.length
			? sections.map( ( block ) =>
					el(
						Flex,
						{ key: block.clientId, align: 'center', style: { marginBottom: '12px' } },
						el(
							FlexItem,
							{ isBlock: true },
							el( ToggleControl, {
								label: label( block ),
								checked: block.attributes?.metadata?.blockVisibility !== false,
								onChange: ( v ) => setVisible( block, v ),
								__nextHasNoMarginBottom: true,
							} )
						),
						el(
							FlexItem,
							null,
							el( Button, {
								variant: 'tertiary',
								size: 'small',
								onClick: () => selectBlock( block.clientId ),
								label: __( 'Select section', 'podcast-core-plugin' ),
								showTooltip: true,
								children: __( 'Edit', 'podcast-core-plugin' ),
							} )
						)
					)
			  )
			: el( 'p', null, __( 'Open a template or a page to see its sections.', 'podcast-core-plugin' ) );

		return el(
			Fragment,
			null,
			el( PluginSidebarMoreMenuItem, { target: 'podcast-core-sections', icon: 'layout' }, __( 'Sections', 'podcast-core-plugin' ) ),
			el(
				PluginSidebar,
				{ name: 'podcast-core-sections', title: __( 'Sections', 'podcast-core-plugin' ), icon: 'layout' },
				el(
					PanelBody,
					null,
					el( 'p', { style: { marginTop: 0 } }, __( 'Switch sections on or off. Hidden sections stay in the page and can be switched back on at any time.', 'podcast-core-plugin' ) ),
					content,
					el(
						'p',
						{ style: { color: '#757575', fontSize: '12px' } },
						__( 'Spacing and colours of each kind of section: Design → Styles → Blocks.', 'podcast-core-plugin' )
					)
				)
			)
		);
	}

	registerPlugin( 'podcast-core-sections', { render: SectionsPanel } );
} )( window.wp );
