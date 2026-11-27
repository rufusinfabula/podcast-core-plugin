<?php
/**
 * Block registration.
 *
 * @package PodcastCore
 */

namespace PodcastCore;

defined( 'ABSPATH' ) || exit;

final class Blocks {

	/**
	 * "Watch the video" link the Episode Player left for the episode text (position "In the text").
	 *
	 * @var string
	 */
	public static $video_link = '';

	/**
	 * After which block of the episode text the link goes (1 = after the first).
	 *
	 * @var int
	 */
	public static $video_link_after = 1;

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'render_block_core/post-content', array( __CLASS__, 'video_link_in_text' ), 10, 2 );
	}

	/**
	 * Puts the player's "Watch the video" link after the Nth block of the episode text
	 * (paragraph, list, heading, quote or figure); after the last one if the text is shorter,
	 * at the start if there is none.
	 */
	public static function video_link_in_text( string $html, array $block ): string {
		if ( '' === self::$video_link || PODCAST_CORE_EPISODE !== get_post_type() ) {
			return $html;
		}
		$link             = self::$video_link;
		self::$video_link = '';
		if ( preg_match_all( '~</(p|ul|ol|h[2-6]|blockquote|figure)>~i', $html, $m, PREG_OFFSET_CAPTURE ) ) {
			$end = $m[0][ min( self::$video_link_after, count( $m[0] ) ) - 1 ];
			$at  = $end[1] + strlen( $end[0] );
			return substr( $html, 0, $at ) . $link . substr( $html, $at );
		}
		$open = strpos( $html, '>' );
		return false === $open ? $link . $html : substr( $html, 0, $open + 1 ) . $link . substr( $html, $open + 1 );
	}

	/**
	 * Section blocks share one editor script.
	 */
	public const SECTIONS = array( 'hero', 'episodes-section', 'news-section', 'cta-section', 'partners-section' );

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

		foreach ( array( 'episode-player', 'episode-video', 'partners', 'series-list' ) as $block ) {
			$type = register_block_type( PODCAST_CORE_DIR . 'blocks/' . $block );
			if ( $type && ! empty( $type->editor_script_handles ) ) {
				foreach ( $type->editor_script_handles as $handle ) {
					wp_set_script_translations( $handle, 'podcast-core-engine', PODCAST_CORE_DIR . 'languages' );
				}
			}
		}
	}
}
