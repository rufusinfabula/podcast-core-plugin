<?php
/**
 * Episode artwork from Spotify: the horizontal (16:9, 640×360) image Spotify shows for
 * each episode, via the official oEmbed endpoint. The open.spotify.com link of each
 * episode comes from, in order: the "Spotify link" field of the episode; the public
 * Spotify for Creators page (shows hosted there); the Spotify Web API, if a Client ID
 * and Secret are set (shows hosted anywhere). Without a link the feed image is kept.
 *
 * @package PodcastCore
 */

namespace PodcastCore;

defined( 'ABSPATH' ) || exit;

final class Spotify {

	public const META = 'podcast_core_spotify_url';

	/**
	 * open.spotify.com URL of an episode, resolved once and stored.
	 */
	public static function episode_url( int $post_id ): string {
		$url = (string) get_post_meta( $post_id, self::META, true );
		if ( $url ) {
			return $url;
		}
		$url = '';
		$source = (string) get_post_meta( $post_id, 'podcast_core_source_url', true );
		if ( preg_match( '#^https://(podcasters|creators)\.spotify\.com/.+-([a-z0-9]{5,10})/?$#', $source, $m ) ) {
			$map = self::episode_map( $source );
			$url = $map[ $m[2] ] ?? '';
		}
		if ( ! $url && self::api_configured() ) {
			$url = self::match( get_the_title( $post_id ), (string) get_the_date( 'Y-m-d', $post_id ), self::api_episodes() );
		}
		if ( $url ) {
			update_post_meta( $post_id, self::META, $url );
		}
		return $url;
	}

	/* ---------------------------------------------------------------------
	 * Spotify Web API (optional): finds the Spotify link of episodes hosted
	 * anywhere, using the show link (Podcast → Settings → Podcast → Spotify) and the
	 * Client ID / Secret of an app created on developer.spotify.com.
	 * ------------------------------------------------------------------ */

	public static function api_configured(): bool {
		return '' !== (string) Settings::get( 'spotify_client_id' ) && '' !== (string) Settings::get( 'spotify_client_secret' ) && '' !== self::show_id();
	}

	/**
	 * Show id from the show link in Podcast → Settings → Podcast (open.spotify.com/show/…).
	 */
	public static function show_id(): string {
		return preg_match( '#open\.spotify\.com/(?:intl-[a-z-]+/)?show/([A-Za-z0-9]{22})#', (string) Settings::get( 'spotify_url' ), $m ) ? $m[1] : '';
	}

	/**
	 * Client-credentials access token, cached until it expires.
	 *
	 * @return string|\WP_Error
	 */
	private static function token() {
		$id     = (string) Settings::get( 'spotify_client_id' );
		$secret = (string) Settings::get( 'spotify_client_secret' );
		$key    = 'podcast_core_spotify_token_' . md5( $id . $secret );
		$token  = get_transient( $key );
		if ( $token ) {
			return $token;
		}
		$response = wp_safe_remote_post(
			'https://accounts.spotify.com/api/token',
			array(
				'timeout' => 20,
				'headers' => array( 'Authorization' => 'Basic ' . base64_encode( $id . ':' . $secret ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				'body'    => array( 'grant_type' => 'client_credentials' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['access_token'] ) ) {
			return new \WP_Error( 'podcast_core_spotify_auth', __( 'Spotify refused the Client ID / Client Secret.', 'podcast-core-engine' ) );
		}
		set_transient( $key, $data['access_token'], max( 60, (int) ( $data['expires_in'] ?? 3600 ) - 120 ) );
		return $data['access_token'];
	}

	/**
	 * @return array|\WP_Error Decoded JSON.
	 */
	private static function api_get( string $path, array $query = array() ) {
		$token = self::token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}
		$response = wp_safe_remote_get(
			add_query_arg( $query, 'https://api.spotify.com/v1' . $path ),
			array(
				'timeout' => 20,
				'headers' => array( 'Authorization' => 'Bearer ' . $token ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 200 !== $code || ! is_array( $data ) ) {
			/* translators: %d: HTTP status code */
			return new \WP_Error( 'podcast_core_spotify_api', sprintf( __( 'Spotify API error (HTTP %d).', 'podcast-core-engine' ), $code ) );
		}
		return $data;
	}

	private static function market(): string {
		$country = strtoupper( substr( (string) get_locale(), 3, 2 ) );
		return preg_match( '/^[A-Z]{2}$/', $country ) ? $country : 'US';
	}

	/**
	 * Name of the show on Spotify, to check that the API settings work.
	 *
	 * @return string|\WP_Error
	 */
	public static function api_show_name() {
		$data = self::api_get( '/shows/' . self::show_id(), array( 'market' => self::market() ) );
		return is_wp_error( $data ) ? $data : (string) ( $data['name'] ?? '' );
	}

	/**
	 * All episodes of the show on Spotify: name, release date, URL. Cached for 6 hours.
	 *
	 * @return array<int, array{name: string, date: string, url: string}>
	 */
	public static function api_episodes(): array {
		$show = self::show_id();
		$key  = 'podcast_core_spotify_episodes_' . $show;
		$list = get_transient( $key );
		if ( is_array( $list ) ) {
			return $list;
		}
		$list   = array();
		$offset = 0;
		do {
			$data = self::api_get(
				'/shows/' . $show . '/episodes',
				array(
					'market' => self::market(),
					'limit'  => 50,
					'offset' => $offset,
				)
			);
			if ( is_wp_error( $data ) ) {
				break;
			}
			foreach ( (array) ( $data['items'] ?? array() ) as $item ) {
				if ( ! empty( $item['external_urls']['spotify'] ) ) {
					$list[] = array(
						'name' => (string) $item['name'],
						'date' => (string) ( $item['release_date'] ?? '' ),
						'url'  => (string) $item['external_urls']['spotify'],
					);
				}
			}
			$offset += 50;
		} while ( ! empty( $data['next'] ) && $offset < 5000 );

		set_transient( $key, $list, $list ? 6 * HOUR_IN_SECONDS : 10 * MINUTE_IN_SECONDS );
		return $list;
	}

	/**
	 * Spotify URL of the episode with the same title (ignoring case, accents and
	 * punctuation); when titles differ slightly, the most similar title published
	 * within two days.
	 *
	 * @param array $episodes From api_episodes().
	 */
	public static function match( string $title, string $date, array $episodes ): string {
		$wanted = self::normalize( $title );
		if ( '' === $wanted ) {
			return '';
		}
		$best  = '';
		$score = 0.0;
		foreach ( $episodes as $episode ) {
			$name = self::normalize( $episode['name'] );
			if ( $name === $wanted ) {
				return $episode['url'];
			}
			$days = ( $date && $episode['date'] ) ? abs( strtotime( $date ) - strtotime( $episode['date'] ) ) / DAY_IN_SECONDS : 99;
			if ( $days > 2 ) {
				continue;
			}
			similar_text( $wanted, $name, $percent );
			if ( $percent > $score ) {
				$score = $percent;
				$best  = $episode['url'];
			}
		}
		return $score >= 80 ? $best : '';
	}

	private static function normalize( string $text ): string {
		$text = remove_accents( html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		$text = strtolower( preg_replace( '/[^A-Za-z0-9]+/', ' ', $text ) );
		return trim( preg_replace( '/\s+/', ' ', $text ) );
	}

	/**
	 * Creators episode id => open.spotify.com URL, from one public episode page (it lists
	 * the whole show). Cached for an hour.
	 */
	private static function episode_map( string $page ): array {
		// One map per show: cache key without the episode part of the URL.
		$key = 'podcast_core_spotify_map_' . md5( (string) preg_replace( '#/episodes/.*#', '', $page ) );
		$map = get_transient( $key );
		if ( is_array( $map ) ) {
			return $map;
		}
		$response = wp_safe_remote_get(
			$page,
			array(
				'timeout'    => 30,
				'user-agent' => 'Mozilla/5.0 (compatible; Podcast Core Engine/' . PODCAST_CORE_VERSION . ')',
			)
		);
		$map      = array();
		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$html = str_replace( array( '\\"', '\\u002F' ), array( '"', '/' ), wp_remote_retrieve_body( $response ) );
			if ( preg_match_all( '#"episodeId":"([a-z0-9]+)".{0,2000}?"spotifyUrl":"(https://open\.spotify\.com/episode/[A-Za-z0-9]+)"#s', $html, $all, PREG_SET_ORDER ) ) {
				foreach ( $all as $pair ) {
					$map[ $pair[1] ] = $pair[2];
				}
			}
		}
		set_transient( $key, $map, $map ? HOUR_IN_SECONDS : 10 * MINUTE_IN_SECONDS );
		return $map;
	}

	/**
	 * Image URL Spotify shows for an episode (oEmbed thumbnail).
	 */
	public static function thumbnail( string $episode_url ): string {
		$response = wp_safe_remote_get( 'https://open.spotify.com/oembed?url=' . rawurlencode( $episode_url ), array( 'timeout' => 20 ) );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return '';
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		return is_array( $data ) && ! empty( $data['thumbnail_url'] ) ? esc_url_raw( $data['thumbnail_url'] ) : '';
	}

	/**
	 * Sets the Spotify image as featured image. Images chosen by hand are kept unless $force.
	 *
	 * @return int|\WP_Error Attachment ID.
	 */
	public static function apply_image( int $post_id, bool $force = false ) {
		if ( ! $force && ! self::replaceable( $post_id ) ) {
			return new \WP_Error( 'podcast_core_custom_image', __( 'The episode has an image chosen by hand.', 'podcast-core-engine' ) );
		}
		$url = self::episode_url( $post_id );
		if ( ! $url ) {
			return new \WP_Error( 'podcast_core_no_spotify', __( 'Spotify link of the episode not found.', 'podcast-core-engine' ) );
		}
		$thumb = self::thumbnail( $url );
		if ( ! $thumb ) {
			return new \WP_Error( 'podcast_core_no_thumbnail', __( 'Spotify did not return an image for this episode.', 'podcast-core-engine' ) );
		}
		$image = Importer::image( $thumb, $post_id, get_the_title( $post_id ) );
		if ( ! is_wp_error( $image ) ) {
			set_post_thumbnail( $post_id, $image );
		}
		return $image;
	}

	/**
	 * True when the episode has no image, or one that came from the feed.
	 */
	public static function replaceable( int $post_id ): bool {
		$current = get_post_thumbnail_id( $post_id );
		if ( ! $current ) {
			return true;
		}
		$source = (string) get_post_meta( $current, '_podcast_core_source_url', true );
		return '' !== $source && false === strpos( $source, 'spotifycdn.com' );
	}

	/**
	 * Episodes whose image can still be replaced with the Spotify one.
	 *
	 * @return int[]
	 */
	public static function pending(): array {
		$ids = get_posts(
			array(
				'post_type'      => PODCAST_CORE_EPISODE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => 'podcast_core_source_url', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_compare'   => 'EXISTS',
			)
		);
		return array_values( array_filter( $ids, array( __CLASS__, 'replaceable' ) ) );
	}

	/**
	 * Deletes feed images no longer used by any episode (e.g. after switching to Spotify images).
	 */
	public static function delete_unused_feed_images(): int {
		$ids     = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_podcast_core_source_url', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
		);
		$deleted = 0;
		foreach ( $ids as $id ) {
			$source = (string) get_post_meta( $id, '_podcast_core_source_url', true );
			if ( false !== strpos( $source, 'spotifycdn.com' ) ) {
				continue;
			}
			global $wpdb;
			$used = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %d", $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			if ( ! $used && wp_delete_attachment( $id, true ) ) {
				++$deleted;
			}
		}
		return $deleted;
	}
}
