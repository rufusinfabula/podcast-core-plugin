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

	public static function register(): void {
		foreach ( array( 'episode-player' ) as $block ) {
			$type = register_block_type( PODCAST_CORE_DIR . 'blocks/' . $block );
			if ( $type && ! empty( $type->editor_script_handles ) ) {
				foreach ( $type->editor_script_handles as $handle ) {
					wp_set_script_translations( $handle, 'podcast-core-plugin', PODCAST_CORE_DIR . 'languages' );
				}
			}
		}
	}
}
