<?php
/**
 * Main plugin bootstrap.
 *
 * @package PodcastCore
 */

namespace PodcastCore;

defined( 'ABSPATH' ) || exit;

/**
 * Wires up all plugin components.
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
		add_action( 'init', array( $this, 'load_textdomain' ), 0 );

		Settings::init();
		Post_Types::init();
		Episode_Meta::init();
		Editor::init();
		Blocks::init();
		Schema::init();
		Importer::init();
		Import_Page::init();
		Admin_List::init();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'podcast-core', CLI::class );
		}
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'podcast-core-engine', false, dirname( plugin_basename( PODCAST_CORE_FILE ) ) . '/languages' );
	}

	/**
	 * Registers content types and flushes rewrite rules so archive URLs work immediately.
	 */
	public static function activate(): void {
		Post_Types::register();
		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( Importer::CRON_HOOK );
		flush_rewrite_rules();
	}
}
