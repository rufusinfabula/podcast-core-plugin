<?php
/**
 * Block registration.
 *
 * @package PodcastCore
 */

namespace PodcastCore;

defined( 'ABSPATH' ) || exit;

final class Blocks {

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Section blocks share one editor script.
	 */
	public const SECTIONS = array( 'hero', 'episodes-section', 'news-section', 'cta-section' );

	public static function register(): void {
		wp_register_script(
			'podcast-core-sections-editor',
			PODCAST_CORE_URL . 'blocks/sections/editor.js',
			array( 'wp-blocks', 'wp-block-editor', 'wp-element' ),
			PODCAST_CORE_VERSION,
			true
		);
		foreach ( self::SECTIONS as $section ) {
			register_block_type( PODCAST_CORE_DIR . 'blocks/sections/' . $section );
		}

		foreach ( array( 'episode-player' ) as $block ) {
			$type = register_block_type( PODCAST_CORE_DIR . 'blocks/' . $block );
			if ( $type && ! empty( $type->editor_script_handles ) ) {
				foreach ( $type->editor_script_handles as $handle ) {
					wp_set_script_translations( $handle, 'podcast-core-engine', PODCAST_CORE_DIR . 'languages' );
				}
			}
		}
	}
}
