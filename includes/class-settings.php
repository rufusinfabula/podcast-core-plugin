<?php
/**
 * Settings page (Settings → Podcast).
 *
 * @package PodcastCore
 */

namespace PodcastCore;

defined( 'ABSPATH' ) || exit;

/**
 * Stores all options in a single array option.
 */
final class Settings {

	public const OPTION = 'podcast_core_settings';
	private const PAGE  = 'podcast-core';

	/**
	 * Default values. Platform links describe the whole show, not single episodes.
	 */
	public static function defaults(): array {
		return array(
			'episodes_slug' => 'episodes',
			'series_slug'   => 'series',
			'per_page'      => 0,
			'show_name'     => '',
			'spotify_url'   => '',
			'apple_url'     => '',
			'youtube_url'   => '',
			'rss_url'       => '',
			'feed_url'      => '',
			'auto_sync'     => true,
			'image_source'  => 'feed',
		);
	}

	public static function init(): void {
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'update_option_' . self::OPTION, array( __CLASS__, 'maybe_flush' ), 10, 2 );
		add_action( 'init', array( __CLASS__, 'flush_if_needed' ), 99 );
	}

	public static function get( string $key ) {
		$settings = wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
		return $settings[ $key ] ?? null;
	}

	public static function register(): void {
		register_setting(
			self::PAGE,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section( 'podcast_core_urls', __( 'Addresses', 'podcast-core-plugin' ), '__return_false', self::PAGE );
		self::field( 'episodes_slug', __( 'Episodes base', 'podcast-core-plugin' ), 'podcast_core_urls', 'text', __( 'URL of the episode archive, e.g. /episodes/', 'podcast-core-plugin' ) );
		self::field( 'series_slug', __( 'Series base', 'podcast-core-plugin' ), 'podcast_core_urls', 'text', __( 'URL prefix of series pages, e.g. /series/name/', 'podcast-core-plugin' ) );
		self::field( 'per_page', __( 'Episodes per page', 'podcast-core-plugin' ), 'podcast_core_urls', 'number', __( 'In the episode archive and on series pages. 0 = same as Settings → Reading.', 'podcast-core-plugin' ) );

		add_settings_section( 'podcast_core_show', __( 'Show', 'podcast-core-plugin' ), '__return_false', self::PAGE );
		self::field( 'show_name', __( 'Show name', 'podcast-core-plugin' ), 'podcast_core_show', 'text', __( 'Leave empty to use the site title.', 'podcast-core-plugin' ) );
		self::field( 'spotify_url', __( 'Spotify', 'podcast-core-plugin' ), 'podcast_core_show', 'url' );
		self::field( 'apple_url', __( 'Apple Podcasts', 'podcast-core-plugin' ), 'podcast_core_show', 'url' );
		self::field( 'youtube_url', __( 'YouTube', 'podcast-core-plugin' ), 'podcast_core_show', 'url' );
		self::field( 'rss_url', __( 'RSS feed', 'podcast-core-plugin' ), 'podcast_core_show', 'url' );

		add_settings_section( 'podcast_core_import', __( 'Import from RSS', 'podcast-core-plugin' ), '__return_false', self::PAGE );
		self::field( 'feed_url', __( 'Feed to import', 'podcast-core-plugin' ), 'podcast_core_import', 'url', __( 'RSS address of the podcast on your hosting platform (Spotify for Creators, Anchor, Spreaker…). Leave empty to use the RSS feed above.', 'podcast-core-plugin' ) );
		self::field( 'auto_sync', __( 'Automatic update', 'podcast-core-plugin' ), 'podcast_core_import', 'checkbox', __( 'Check the feed every hour and import new episodes.', 'podcast-core-plugin' ) );
		self::field(
			'image_source',
			__( 'Episode image', 'podcast-core-plugin' ),
			'podcast_core_import',
			'select',
			__( 'Spotify images are the horizontal covers shown in the Spotify app (640×360). If Spotify does not provide one, the feed image is used.', 'podcast-core-plugin' ),
			array(
				'feed'    => __( 'From the feed (usually square)', 'podcast-core-plugin' ),
				'spotify' => __( 'From Spotify (horizontal 16:9)', 'podcast-core-plugin' ),
			)
		);
	}

	private static function field( string $key, string $label, string $section, string $type, string $help = '', array $options = array() ): void {
		add_settings_field(
			'podcast_core_' . $key,
			$label,
			function () use ( $key, $type, $help, $options ) {
				if ( 'select' === $type ) {
					printf( '<select id="podcast_core_%1$s" name="%2$s[%1$s]">', esc_attr( $key ), esc_attr( self::OPTION ) );
					foreach ( $options as $value => $text ) {
						printf( '<option value="%s" %s>%s</option>', esc_attr( $value ), selected( self::get( $key ), $value, false ), esc_html( $text ) );
					}
					echo '</select>';
					if ( $help ) {
						printf( '<p class="description">%s</p>', esc_html( $help ) );
					}
					return;
				}
				if ( 'checkbox' === $type ) {
					printf(
						'<label><input type="checkbox" id="podcast_core_%1$s" name="%2$s[%1$s]" value="1" %3$s /> %4$s</label>',
						esc_attr( $key ),
						esc_attr( self::OPTION ),
						checked( (bool) self::get( $key ), true, false ),
						esc_html( $help )
					);
					return;
				}
				printf(
					'<input type="%1$s" id="podcast_core_%2$s" name="%3$s[%2$s]" value="%4$s" class="' . ( 'number' === $type ? 'small-text" min="0" step="1' : 'regular-text' ) . '" />',
					esc_attr( $type ),
					esc_attr( $key ),
					esc_attr( self::OPTION ),
					esc_attr( (string) self::get( $key ) )
				);
				if ( $help ) {
					printf( '<p class="description">%s</p>', esc_html( $help ) );
				}
			},
			self::PAGE,
			$section,
			array( 'label_for' => 'podcast_core_' . $key )
		);
	}

	public static function sanitize( $input ): array {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();
		$clean    = array();

		foreach ( array( 'episodes_slug', 'series_slug' ) as $key ) {
			$slug          = sanitize_title( $input[ $key ] ?? '' );
			$clean[ $key ] = '' !== $slug ? $slug : $defaults[ $key ];
		}
		$clean['per_page']  = min( 100, absint( $input['per_page'] ?? 0 ) );
		$clean['show_name'] = sanitize_text_field( $input['show_name'] ?? '' );
		foreach ( array( 'spotify_url', 'apple_url', 'youtube_url', 'rss_url', 'feed_url' ) as $key ) {
			$clean[ $key ] = esc_url_raw( $input[ $key ] ?? '' );
		}
		$clean['auto_sync']    = ! empty( $input['auto_sync'] );
		$clean['image_source'] = in_array( $input['image_source'] ?? '', array( 'feed', 'spotify' ), true ) ? $input['image_source'] : 'feed';
		return $clean;
	}

	public static function add_page(): void {
		add_options_page(
			__( 'Podcast', 'podcast-core-plugin' ),
			__( 'Podcast', 'podcast-core-plugin' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' )
		);
	}

	public static function render_page(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Podcast settings', 'podcast-core-plugin' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::PAGE );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Slugs changed: rewrite rules must be rebuilt after the post types re-register.
	 */
	public static function maybe_flush( $old, $new ): void {
		$old = (array) $old;
		$new = (array) $new;
		if ( ( $old['episodes_slug'] ?? '' ) !== ( $new['episodes_slug'] ?? '' ) || ( $old['series_slug'] ?? '' ) !== ( $new['series_slug'] ?? '' ) ) {
			update_option( 'podcast_core_flush_rewrite', 1 );
		}
	}

	public static function flush_if_needed(): void {
		if ( get_option( 'podcast_core_flush_rewrite' ) ) {
			delete_option( 'podcast_core_flush_rewrite' );
			flush_rewrite_rules();
		}
	}
}
