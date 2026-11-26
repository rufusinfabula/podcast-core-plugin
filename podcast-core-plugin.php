<?php
/**
 * Plugin Name:       Podcast Core Plugin
 * Plugin URI:        https://github.com/rufusinfabula/podcast-core-plugin
 * Description:       Episodes, series and an audio player for podcast websites. Lightweight, no third-party dependencies.
 * Version:           0.5.0
 * Requires at least: 6.7
 * Requires PHP:      8.1
 * Author:            Rufusinfabula
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       podcast-core-plugin
 * Domain Path:       /languages
 *
 * @package PodcastCore
 */

defined( 'ABSPATH' ) || exit;

define( 'PODCAST_CORE_VERSION', '0.5.0' );
define( 'PODCAST_CORE_FILE', __FILE__ );
define( 'PODCAST_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'PODCAST_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once PODCAST_CORE_DIR . 'includes/functions.php';
require_once PODCAST_CORE_DIR . 'includes/class-settings.php';
require_once PODCAST_CORE_DIR . 'includes/class-post-types.php';
require_once PODCAST_CORE_DIR . 'includes/class-episode-meta.php';
require_once PODCAST_CORE_DIR . 'includes/class-editor.php';
require_once PODCAST_CORE_DIR . 'includes/class-blocks.php';
require_once PODCAST_CORE_DIR . 'includes/class-schema.php';
require_once PODCAST_CORE_DIR . 'includes/class-feed.php';
require_once PODCAST_CORE_DIR . 'includes/class-importer.php';
require_once PODCAST_CORE_DIR . 'includes/class-spotify.php';
require_once PODCAST_CORE_DIR . 'includes/class-import-page.php';
require_once PODCAST_CORE_DIR . 'includes/class-plugin.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once PODCAST_CORE_DIR . 'includes/class-cli.php';
}

register_activation_hook( __FILE__, array( 'PodcastCore\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PodcastCore\Plugin', 'deactivate' ) );

PodcastCore\Plugin::instance();
