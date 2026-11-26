<?php
/**
 * "Episode details" panel in the block editor sidebar.
 *
 * @package PodcastCore
 */

namespace PodcastCore;

defined( 'ABSPATH' ) || exit;

final class Editor {

	public static function init(): void {
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue' ) );
	}

	public static function enqueue(): void {
		$screen = get_current_screen();

		// "Sections" panel: Site Editor and pages/posts in the block editor.
		wp_enqueue_script(
			'podcast-core-sections-panel',
			PODCAST_CORE_URL . 'assets/js/sections-panel.js',
			array( 'wp-plugins', 'wp-editor', 'wp-components', 'wp-data', 'wp-blocks', 'wp-element', 'wp-i18n' ),
			PODCAST_CORE_VERSION,
			true
		);
		wp_set_script_translations( 'podcast-core-sections-panel', 'podcast-core-engine', PODCAST_CORE_DIR . 'languages' );

		if ( ! $screen || PODCAST_CORE_EPISODE !== $screen->post_type ) {
			return;
		}
		wp_enqueue_script(
			'podcast-core-episode-panel',
			PODCAST_CORE_URL . 'assets/js/episode-panel.js',
			array( 'wp-plugins', 'wp-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data', 'wp-i18n' ),
			PODCAST_CORE_VERSION,
			true
		);
		wp_set_script_translations( 'podcast-core-episode-panel', 'podcast-core-engine', PODCAST_CORE_DIR . 'languages' );
	}
}
