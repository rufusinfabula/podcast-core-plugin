<?php
/**
 * Plugin Name:       Podcast Core Engine
 * Plugin URI:        https://github.com/rufusinfabula/podcast-core-engine
 * Description:       Publish your podcast on WordPress: episodes, series, a sticky audio player and automatic import from your RSS feed (Spotify, Spreaker…).
 * Version:           0.10.0
 * Requires at least: 6.7
 * Requires PHP:      8.1
 * Author:            Fabio Ranfi Solutions
 * Author URI:        https://github.com/rufusinfabula
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       podcast-core-engine
 * Domain Path:       /languages
 *
 * @package PodcastCore
 */

defined( 'ABSPATH' ) || exit;

define( 'PODCAST_CORE_VERSION', '0.10.0' );
define( 'PODCAST_CORE_FILE', __FILE__ );
define( 'PODCAST_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'PODCAST_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once PODCAST_CORE_DIR . 'includes/functions.php';
require_once PODCAST_CORE_DIR . 'includes/class-settings.php';
require_once PODCAST_CORE_DIR . 'includes/class-post-types.php';
require_once PODCAST_CORE_DIR . 'includes/class-partners.php';
require_once PODCAST_CORE_DIR . 'includes/class-series-icons.php';
require_once PODCAST_CORE_DIR . 'includes/class-episode-meta.php';
require_once PODCAST_CORE_DIR . 'includes/class-editor.php';
require_once PODCAST_CORE_DIR . 'includes/class-blocks.php';
require_once PODCAST_CORE_DIR . 'includes/class-schema.php';
require_once PODCAST_CORE_DIR . 'includes/class-feed.php';
require_once PODCAST_CORE_DIR . 'includes/class-importer.php';
require_once PODCAST_CORE_DIR . 'includes/class-spotify.php';
require_once PODCAST_CORE_DIR . 'includes/class-import-page.php';
require_once PODCAST_CORE_DIR . 'includes/class-admin-list.php';
require_once PODCAST_CORE_DIR . 'includes/class-consent.php';
require_once PODCAST_CORE_DIR . 'includes/class-social.php';
require_once PODCAST_CORE_DIR . 'includes/class-plugin.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once PODCAST_CORE_DIR . 'includes/class-cli.php';
}

register_activation_hook( __FILE__, array( 'PodcastCore\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PodcastCore\Plugin', 'deactivate' ) );

PodcastCore\Plugin::instance();
