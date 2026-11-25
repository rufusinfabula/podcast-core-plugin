/**
 * Episode details panel (no build step: plain wp.* globals).
 */
( function ( wp ) {
	const { registerPlugin } = wp.plugins;
	const { PluginDocumentSettingPanel } = wp.editor;
	const { TextControl, __experimentalNumberControl: NumberControl } = wp.components;
	const { useSelect } = wp.data;
	const { useEntityProp } = wp.coreData;
	const { createElement: el } = wp.element;
	const { __ } = wp.i18n;

	const NumberField = NumberControl || TextControl;

	function EpisodePanel() {
		const postType = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );
		const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

		if ( 'podcast_episode' !== postType || ! meta ) {
			return null;
		}

		const set = ( key, value ) => setMeta( { ...meta, [ key ]: value } );
		const toInt = ( value ) => {
			const n = parseInt( value, 10 );
			return Number.isNaN( n ) ? 0 : n;
		};

		return el(
			PluginDocumentSettingPanel,
			{ name: 'podcast-core-episode', title: __( 'Episode details', 'podcast-core-plugin' ), initialOpen: true },
			el( NumberField, {
				label: __( 'Episode number', 'podcast-core-plugin' ),
				min: 0,
				value: meta.podcast_core_episode_number || '',
				onChange: ( v ) => set( 'podcast_core_episode_number', toInt( v ) ),
				__next40pxDefaultSize: true,
			} ),
			el( NumberField, {
				label: __( 'Season', 'podcast-core-plugin' ),
				min: 0,
				value: meta.podcast_core_season_number || '',
				onChange: ( v ) => set( 'podcast_core_season_number', toInt( v ) ),
				__next40pxDefaultSize: true,
			} ),
			el( TextControl, {
				label: __( 'Audio file URL (MP3)', 'podcast-core-plugin' ),
				type: 'url',
				value: meta.podcast_core_audio_url || '',
				onChange: ( v ) => set( 'podcast_core_audio_url', v ),
				__next40pxDefaultSize: true,
				__nextHasNoMarginBottom: true,
			} ),
			el( TextControl, {
				label: __( 'Duration', 'podcast-core-plugin' ),
				help: __( 'HH:MM:SS, MM:SS or seconds.', 'podcast-core-plugin' ),
				value: meta.podcast_core_duration || '',
				onChange: ( v ) => set( 'podcast_core_duration', v ),
				__next40pxDefaultSize: true,
				__nextHasNoMarginBottom: true,
			} ),
			el( TextControl, {
				label: __( 'Video URL (YouTube, Vimeo…)', 'podcast-core-plugin' ),
				type: 'url',
				value: meta.podcast_core_video_url || '',
				onChange: ( v ) => set( 'podcast_core_video_url', v ),
				__next40pxDefaultSize: true,
				__nextHasNoMarginBottom: true,
			} )
		);
	}

	registerPlugin( 'podcast-core-episode-panel', { render: EpisodePanel } );
} )( window.wp );
