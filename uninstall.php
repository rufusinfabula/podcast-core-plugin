<?php
/**
 * Uninstall: removes plugin options only. Episodes and their data are content and are kept.
 *
 * @package PodcastCore
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'podcast_core_settings' );
delete_option( 'podcast_core_flush_rewrite' );
delete_option( 'podcast_core_import_status' );
wp_clear_scheduled_hook( 'podcast_core_feed_sync' );
