<?php
/**
 * Settings: Podcast → Settings, one admin page with three tabs —
 * Podcast (show and platforms), Settings (addresses, order, RSS import, Spotify API) and
 * Styles (hero images). All values live in one array option; each tab saves only its own keys.
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
	private const PAGE  = 'podcast-core';  // settings group and admin page slug.

	/**
	 * Pages of the menu: slug suffix => keys saved by that page.
	 */
	private const TABS = array(
		'podcast'  => array( 'show_name', 'spotify_url', 'apple_url', 'youtube_url', 'rss_url', 'newsletter_url' ),
		'settings' => array( 'episodes_slug', 'series_slug', 'per_page', 'order', 'feed_url', 'auto_sync', 'image_source', 'spotify_client_id', 'spotify_client_secret' ),
		'styles'   => array( 'hero_images' ),
	);

	/** @var string Admin page hook suffix. */
	private static $hook = '';

	/**
	 * Settings-sections page id of a tab.
	 */
	private static function page( string $tab ): string {
		return self::PAGE . '-' . $tab;
	}

	/**
	 * Current tab (?tab=), default Podcast.
	 */
	private static function current_tab(): string {
		$tab = sanitize_key( $_GET['tab'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( self::TABS[ $tab ] ) ? $tab : 'podcast';
	}

	/**
	 * Default values. Platform links describe the whole show, not single episodes.
	 */
	public static function defaults(): array {
		return array(
			'episodes_slug' => 'episodes',
			'series_slug'   => 'series',
			'per_page'      => 0,
			'order'         => 'desc',
			'show_name'     => '',
			'spotify_url'   => '',
			'apple_url'     => '',
			'youtube_url'   => '',
			'rss_url'       => '',
			'newsletter_url' => '',
			'hero_images'   => array(),
			'feed_url'      => '',
			'auto_sync'     => true,
			'image_source'  => 'feed',
			'spotify_client_id'     => '',
			'spotify_client_secret' => '',
		);
	}

	public static function init(): void {
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ), 20 ); // after the other Podcast pages: last item
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_menu', array( __CLASS__, 'redirect_old_page' ), 1 );
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

		add_settings_section( 'podcast_core_urls', __( 'Addresses', 'podcast-core-engine' ), '__return_false', self::page( 'settings' ) );
		self::field( 'episodes_slug', __( 'Episodes base', 'podcast-core-engine' ), 'podcast_core_urls', 'text', __( 'URL of the episode archive, e.g. /episodes/', 'podcast-core-engine' ) );
		self::field( 'series_slug', __( 'Series base', 'podcast-core-engine' ), 'podcast_core_urls', 'text', __( 'URL prefix of series pages, e.g. /series/name/', 'podcast-core-engine' ) );
		self::field( 'per_page', __( 'Episodes per page', 'podcast-core-engine' ), 'podcast_core_urls', 'number', __( 'In the episode archive and on series pages. 0 = same as Settings → Reading.', 'podcast-core-engine' ) );
		self::field(
			'order',
			__( 'Episode order', 'podcast-core-engine' ),
			'podcast_core_urls',
			'select',
			__( 'In the episode archive and on series pages. Each series can override it (Podcast → Series). Narrative shows usually list the first episode first.', 'podcast-core-engine' ),
			array(
				'desc' => __( 'Newest first', 'podcast-core-engine' ),
				'asc'  => __( 'Oldest first', 'podcast-core-engine' ),
			)
		);

		add_settings_section( 'podcast_core_show', __( 'Show', 'podcast-core-engine' ), '__return_false', self::page( 'podcast' ) );
		self::field( 'show_name', __( 'Show name', 'podcast-core-engine' ), 'podcast_core_show', 'text', __( 'Leave empty to use the site title.', 'podcast-core-engine' ) );
		self::field( 'spotify_url', __( 'Spotify', 'podcast-core-engine' ), 'podcast_core_show', 'url' );
		self::field( 'apple_url', __( 'Apple Podcasts', 'podcast-core-engine' ), 'podcast_core_show', 'url' );
		self::field( 'youtube_url', __( 'YouTube', 'podcast-core-engine' ), 'podcast_core_show', 'url' );
		self::field( 'rss_url', __( 'RSS feed', 'podcast-core-engine' ), 'podcast_core_show', 'url' );
		self::field( 'newsletter_url', __( 'Email sign-up', 'podcast-core-engine' ), 'podcast_core_show', 'url', __( 'Page where listeners sign up to get new episodes or your newsletter by email (Substack, Mailchimp, Brevo…). Used by the theme\'s sign-up buttons.', 'podcast-core-engine' ) );

		add_settings_section(
			'podcast_core_site',
			__( 'Home page', 'podcast-core-engine' ),
			function () {
				echo '<p>' . esc_html__( 'The site icon (favicon), logo, title and tagline are in Appearance → Editor → Design → Identity.', 'podcast-core-engine' ) . '</p>';
			},
			self::page( 'styles' )
		);
		self::field( 'hero_images', __( 'Hero images', 'podcast-core-engine' ), 'podcast_core_site', 'images', __( 'Shown in the hero of the home page by themes that support it (Podcast Core Studio). With more than one image they rotate with a crossfade; drag to change the order. Leave empty to use the images placed in the home page template.', 'podcast-core-engine' ) );

		add_settings_section( 'podcast_core_import', __( 'Import from RSS', 'podcast-core-engine' ), '__return_false', self::page( 'settings' ) );
		self::field( 'feed_url', __( 'Feed to import', 'podcast-core-engine' ), 'podcast_core_import', 'url', __( 'RSS address of the podcast on your hosting platform (Spotify for Creators, Anchor, Spreaker…). Leave empty to use the RSS feed above.', 'podcast-core-engine' ) );
		self::field( 'auto_sync', __( 'Automatic update', 'podcast-core-engine' ), 'podcast_core_import', 'checkbox', __( 'Check the feed every hour and import new episodes.', 'podcast-core-engine' ) );
		self::field(
			'image_source',
			__( 'Episode image', 'podcast-core-engine' ),
			'podcast_core_import',
			'select',
			__( 'Spotify images are the horizontal covers shown in the Spotify app (640×360). If Spotify does not provide one, the feed image is used.', 'podcast-core-engine' ),
			array(
				'feed'    => __( 'From the feed (usually square)', 'podcast-core-engine' ),
				'spotify' => __( 'From Spotify (horizontal 16:9)', 'podcast-core-engine' ),
			)
		);

		add_settings_section(
			'podcast_core_spotify_api',
			__( 'Spotify API (optional)', 'podcast-core-engine' ),
			function () {
				echo '<p>' . esc_html__( 'Needed only if your podcast is not hosted on Spotify for Creators: the plugin then finds the Spotify link of each episode by itself (to get the Spotify image). Requires the show link in the "Spotify" field above.', 'podcast-core-engine' ) . '</p>';
				echo '<ol><li>' . wp_kses( __( 'Sign in on <a href="https://developer.spotify.com/dashboard" target="_blank" rel="noopener">developer.spotify.com/dashboard</a> and create an app (any name; Redirect URI: your site address; API: Web API).', 'podcast-core-engine' ), array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) ) . '</li>';
				echo '<li>' . esc_html__( 'Open the app settings and copy the Client ID and the Client Secret here.', 'podcast-core-engine' ) . '</li></ol>';
			},
			self::page( 'settings' )
		);
		self::field( 'spotify_client_id', __( 'Client ID', 'podcast-core-engine' ), 'podcast_core_spotify_api', 'text' );
		self::field( 'spotify_client_secret', __( 'Client Secret', 'podcast-core-engine' ), 'podcast_core_spotify_api', 'password', __( 'Stored and never shown again. Leave empty to keep the saved one.', 'podcast-core-engine' ) );
	}

	private static function field( string $key, string $label, string $section, string $type, string $help = '', array $options = array() ): void {
		$tab = 'settings';
		foreach ( self::TABS as $name => $keys ) {
			if ( in_array( $key, $keys, true ) ) {
				$tab = $name;
			}
		}
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
				if ( 'images' === $type ) {
					$ids = array_filter( array_map( 'absint', (array) self::get( $key ) ) );
					echo '<div class="podcast-core-images">';
					printf( '<input type="hidden" id="podcast_core_%1$s" name="%2$s[%1$s]" value="%3$s" />', esc_attr( $key ), esc_attr( self::OPTION ), esc_attr( implode( ',', $ids ) ) );
					echo '<ul class="podcast-core-images__list">';
					foreach ( $ids as $id ) {
						printf( '<li data-id="%1$d">%2$s</li>', (int) $id, wp_get_attachment_image( $id, 'thumbnail' ) );
					}
					echo '</ul>';
					printf(
						'<p><button type="button" class="button podcast-core-images__choose">%1$s</button> <button type="button" class="button-link podcast-core-images__clear"%3$s>%2$s</button></p>',
						esc_html__( 'Choose images', 'podcast-core-engine' ),
						esc_html__( 'Remove all', 'podcast-core-engine' ),
						$ids ? '' : ' hidden'
					);
					echo '</div>';
					if ( $help ) {
						printf( '<p class="description">%s</p>', esc_html( $help ) );
					}
					return;
				}
				if ( 'password' === $type ) {
					printf(
						'<input type="password" id="podcast_core_%1$s" name="%2$s[%1$s]" value="" class="regular-text" autocomplete="new-password" placeholder="%3$s" />',
						esc_attr( $key ),
						esc_attr( self::OPTION ),
						esc_attr( self::get( $key ) ? '••••••••••••' : '' )
					);
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
			self::page( $tab ),
			$section,
			array( 'label_for' => 'podcast_core_' . $key )
		);
	}

	public static function sanitize( $input ): array {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();
		$saved    = wp_parse_args( (array) get_option( self::OPTION, array() ), $defaults );
		// A settings page sends its own keys only (and its name in _tab): the others keep their saved value.
		$tab   = sanitize_key( $input['_tab'] ?? '' );
		$keys  = self::TABS[ $tab ] ?? array_keys( $defaults );
		$clean = array();

		foreach ( array( 'episodes_slug', 'series_slug' ) as $key ) {
			$slug          = sanitize_title( $input[ $key ] ?? '' );
			$clean[ $key ] = '' !== $slug ? $slug : $defaults[ $key ];
		}
		$clean['per_page']  = min( 100, absint( $input['per_page'] ?? 0 ) );
		$clean['order']     = 'asc' === ( $input['order'] ?? '' ) ? 'asc' : 'desc';
		$clean['show_name'] = sanitize_text_field( $input['show_name'] ?? '' );
		foreach ( array( 'spotify_url', 'apple_url', 'youtube_url', 'rss_url', 'feed_url', 'newsletter_url' ) as $key ) {
			$clean[ $key ] = esc_url_raw( $input[ $key ] ?? '' );
		}
		$clean['spotify_client_id'] = sanitize_text_field( $input['spotify_client_id'] ?? '' );
		$secret                     = sanitize_text_field( $input['spotify_client_secret'] ?? '' );
		// Empty field = keep the saved secret; removing the Client ID removes the secret too.
		$clean['spotify_client_secret'] = '' === $clean['spotify_client_id'] ? '' : ( '' !== $secret ? $secret : (string) self::get( 'spotify_client_secret' ) );
		$images               = is_array( $input['hero_images'] ?? '' ) ? $input['hero_images'] : explode( ',', (string) ( $input['hero_images'] ?? '' ) );
		$clean['hero_images'] = array_values( array_filter( array_map( 'absint', $images ), 'wp_attachment_is_image' ) );
		$clean['auto_sync']    = ! empty( $input['auto_sync'] );
		$clean['image_source'] = in_array( $input['image_source'] ?? '', array( 'feed', 'spotify' ), true ) ? $input['image_source'] : 'feed';

		$out = array();
		foreach ( $defaults as $key => $default ) {
			$out[ $key ] = in_array( $key, $keys, true ) ? ( $clean[ $key ] ?? $default ) : $saved[ $key ];
		}
		return $out;
	}

	/**
	 * Podcast → Settings, last item of the Podcast menu.
	 */
	public static function add_page(): void {
		self::$hook = (string) add_submenu_page(
			'edit.php?post_type=' . PODCAST_CORE_EPISODE,
			__( 'Podcast Core Engine', 'podcast-core-engine' ),
			_x( 'Settings', 'admin menu', 'podcast-core-engine' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' ),
			99
		);
	}

	/**
	 * Address of a settings tab: podcast | settings | styles.
	 */
	public static function url( string $tab = 'podcast' ): string {
		return add_query_arg( array( 'post_type' => PODCAST_CORE_EPISODE, 'page' => self::PAGE, 'tab' => $tab ), admin_url( 'edit.php' ) );
	}

	/**
	 * Old address (Settings → Podcast, before 0.10) → Podcast → Settings. Runs on admin_menu,
	 * before WordPress checks access to the requested page.
	 */
	public static function redirect_old_page(): void {
		global $pagenow;
		if ( 'options-general.php' === $pagenow && self::PAGE === ( $_GET['page'] ?? '' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_safe_redirect( self::url() );
			exit;
		}
	}

	public static function assets( string $hook ): void {
		if ( self::$hook !== $hook || 'styles' !== self::current_tab() ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'podcast-core-settings-images', PODCAST_CORE_URL . 'assets/js/settings-images.js', array( 'jquery', 'jquery-ui-sortable' ), PODCAST_CORE_VERSION, true );
		wp_localize_script(
			'podcast-core-settings-images',
			'podcastCoreImages',
			array(
				'title'  => __( 'Hero images', 'podcast-core-engine' ),
				'button' => __( 'Use these images', 'podcast-core-engine' ),
			)
		);
		wp_add_inline_style( 'common', '.podcast-core-images__list{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 8px}.podcast-core-images__list li{margin:0;cursor:move}.podcast-core-images__list img{display:block;width:96px;height:96px;object-fit:cover;border-radius:4px}' );
	}

	/**
	 * Hero images chosen in Podcast → Settings → Styles (attachment IDs, in order).
	 *
	 * @return int[]
	 */
	public static function hero_images(): array {
		return array_values( array_filter( array_map( 'absint', (array) self::get( 'hero_images' ) ), 'wp_attachment_is_image' ) );
	}

	public static function render_page(): void {
		$tab    = self::current_tab();
		$titles = array(
			'podcast'  => __( 'Podcast', 'podcast-core-engine' ),
			'settings' => _x( 'General', 'settings tab', 'podcast-core-engine' ),
			'styles'   => __( 'Styles', 'podcast-core-engine' ),
		);
		?>
		<div class="wrap">
			<h1><?php echo esc_html( __( 'Podcast Core Engine', 'podcast-core-engine' ) . ' – ' . $titles[ $tab ] ); ?></h1>
			<nav class="nav-tab-wrapper">
				<?php foreach ( $titles as $name => $label ) : ?>
					<a href="<?php echo esc_url( self::url( $name ) ); ?>" class="nav-tab<?php echo $name === $tab ? ' nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::PAGE );
				printf( '<input type="hidden" name="%s[_tab]" value="%s" />', esc_attr( self::OPTION ), esc_attr( $tab ) );
				do_settings_sections( self::page( $tab ) );
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
