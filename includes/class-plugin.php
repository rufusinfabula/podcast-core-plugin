<?php
/**
 * Main plugin bootstrap.
 *
 * @package PodcastCore
 */

namespace PodcastCore;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin singleton: wires up hooks. Features (episodes, series, player)
 * are added as separate classes in includes/ and registered here.
 */
final class Plugin {

	/** @var Plugin|null */
	private static $instance = null;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'podcast-core-plugin', false, dirname( plugin_basename( PODCAST_CORE_FILE ) ) . '/languages' );
	}
}
