<?php
/**
 * Podcast → Import from RSS: preview, import with progress, "Force update", status.
 *
 * @package PodcastCore
 */

namespace PodcastCore;

defined( 'ABSPATH' ) || exit;

final class Import_Page {

	private const SLUG  = 'podcast-core-import';
	private const BATCH = 5;

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function menu(): void {
		$hook = add_submenu_page(
			'edit.php?post_type=' . PODCAST_CORE_EPISODE,
			__( 'Import from RSS', 'podcast-core-engine' ),
			__( 'Import from RSS', 'podcast-core-engine' ),
			'manage_options',
			self::SLUG,
			array( __CLASS__, 'render' )
		);
		add_action( 'admin_print_scripts-' . $hook, array( __CLASS__, 'assets' ) );
	}

	public static function assets(): void {
		wp_enqueue_script( 'podcast-core-import', PODCAST_CORE_URL . 'assets/js/import-page.js', array( 'wp-api-fetch', 'wp-i18n' ), PODCAST_CORE_VERSION, true );
		wp_set_script_translations( 'podcast-core-import', 'podcast-core-engine', PODCAST_CORE_DIR . 'languages' );
		wp_localize_script( 'podcast-core-import', 'podcastCoreImport', array( 'batch' => self::BATCH ) );
	}

	public static function routes(): void {
		$permission = fn() => current_user_can( 'manage_options' );
		register_rest_route(
			'podcast-core/v1',
			'/import/preview',
			array(
				'methods'             => 'GET',
				'permission_callback' => $permission,
				'callback'            => fn() => self::response( Importer::preview() ),
			)
		);
		register_rest_route(
			'podcast-core/v1',
			'/import/images',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => array( __CLASS__, 'images_batch' ),
			)
		);
		register_rest_route(
			'podcast-core/v1',
			'/import/run',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => fn() => self::response( Importer::sync( 'manual', self::BATCH ) ),
			)
		);
	}

	/**
	 * Replaces feed images with Spotify ones, a few episodes per request.
	 */
	public static function images_batch() {
		$pending = Spotify::pending();
		$done    = 0;
		$errors  = array();
		foreach ( array_slice( $pending, 0, self::BATCH ) as $id ) {
			$result = Spotify::apply_image( $id );
			if ( is_wp_error( $result ) ) {
				// Mark as handled so the batch moves on: keep the feed image, remember why.
				update_post_meta( $id, '_podcast_core_spotify_error', $result->get_error_message() );
				$errors[] = get_the_title( $id ) . ': ' . $result->get_error_message();
			} else {
				++$done;
			}
		}
		$remaining = max( 0, count( $pending ) - self::BATCH );
		if ( ! $remaining ) {
			Spotify::delete_unused_feed_images();
		}
		return rest_ensure_response(
			array(
				'done'      => $done,
				'errors'    => $errors,
				'remaining' => $errors ? 0 : $remaining,
				'total'     => count( $pending ),
			)
		);
	}

	private static function response( $result ) {
		if ( is_wp_error( $result ) ) {
			return new \WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => 400 ) );
		}
		return rest_ensure_response( $result );
	}

	public static function render(): void {
		$feed     = Importer::feed_url();
		$status   = get_option( Importer::STATUS_OPT );
		$next     = wp_next_scheduled( Importer::CRON_HOOK );
		$settings = admin_url( 'options-general.php?page=podcast-core' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import from RSS', 'podcast-core-engine' ); ?></h1>
			<p><?php esc_html_e( 'Creates one episode for each item of your podcast feed: title, description, date, audio, duration, number, season and cover image. Episodes already on the site are recognised and never overwritten, so your edits are safe.', 'podcast-core-engine' ); ?></p>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Feed', 'podcast-core-engine' ); ?></th>
					<td>
						<?php if ( $feed ) : ?>
							<code><?php echo esc_html( $feed ); ?></code>
						<?php else : ?>
							<em><?php esc_html_e( 'Not set.', 'podcast-core-engine' ); ?></em>
						<?php endif; ?>
						— <a href="<?php echo esc_url( $settings ); ?>"><?php esc_html_e( 'Change in Settings → Podcast', 'podcast-core-engine' ); ?></a>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Automatic update', 'podcast-core-engine' ); ?></th>
					<td>
						<?php
						if ( $next ) {
							/* translators: %s: date and time */
							printf( esc_html__( 'On — next check: %s', 'podcast-core-engine' ), esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next ) ) );
						} else {
							esc_html_e( 'Off', 'podcast-core-engine' );
						}
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Spotify API', 'podcast-core-engine' ); ?></th>
					<td>
						<?php
						if ( Spotify::api_configured() ) {
							$name = Spotify::api_show_name();
							if ( is_wp_error( $name ) ) {
								echo esc_html( $name->get_error_message() );
							} else {
								/* translators: %s: show name on Spotify */
								printf( esc_html__( 'Connected — show: %s', 'podcast-core-engine' ), esc_html( $name ) );
							}
						} else {
							esc_html_e( 'Not configured (optional)', 'podcast-core-engine' );
						}
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Last update', 'podcast-core-engine' ); ?></th>
					<td id="pc-import-last">
						<?php
						if ( $status ) {
							$r = $status['result'];
							echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $status['time'] ) ) . ' — ';
							if ( isset( $r['error'] ) ) {
								echo esc_html( $r['error'] );
							} else {
								/* translators: 1: imported episodes, 2: episodes already on the site */
								printf( esc_html__( '%1$d imported, %2$d already on the site', 'podcast-core-engine' ), (int) $r['created'], (int) $r['existing'] );
							}
						} else {
							esc_html_e( 'Never', 'podcast-core-engine' );
						}
						?>
					</td>
				</tr>
			</table>

			<?php if ( $feed ) : ?>
				<p>
					<button type="button" class="button" id="pc-import-preview"><?php esc_html_e( 'Check the feed', 'podcast-core-engine' ); ?></button>
					<button type="button" class="button button-primary" id="pc-import-run"><?php esc_html_e( 'Force update now', 'podcast-core-engine' ); ?></button>
					<button type="button" class="button" id="pc-import-images"><?php esc_html_e( 'Update images from Spotify', 'podcast-core-engine' ); ?></button>
				</p>
				<p class="description"><?php esc_html_e( 'Update images from Spotify: replaces the feed image of existing episodes with the horizontal cover shown on Spotify. Images you chose by hand are kept.', 'podcast-core-engine' ); ?></p>
				<div id="pc-import-output" aria-live="polite"></div>
				<progress id="pc-import-progress" max="100" value="0" style="width:100%;max-width:600px;display:none"></progress>
			<?php endif; ?>
		</div>
		<?php
	}
}
