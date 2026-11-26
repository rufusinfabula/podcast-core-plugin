/**
 * Section blocks (Hero, Episodes, News, Call to action): plain containers whose
 * spacing, colours and typography can be set per block or for every instance in
 * Design → Styles → Blocks.
 */
( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, useInnerBlocksProps, InnerBlocks } = wp.blockEditor;
	const { createElement: el } = wp.element;

	const names = [ 'hero', 'episodes-section', 'news-section', 'cta-section' ];

	names.forEach( ( slug ) => {
		registerBlockType( 'podcast-core/' + slug, {
			edit( { attributes } ) {
				const blockProps = useBlockProps();
				const innerBlocksProps = useInnerBlocksProps( blockProps, {
					layout: attributes.layout,
					renderAppender: InnerBlocks.ButtonBlockAppender,
				} );
				return el( 'div', innerBlocksProps );
			},
			save() {
				return el( 'div', useInnerBlocksProps.save( useBlockProps.save() ) );
			},
		} );
	} );
} )( window.wp );
