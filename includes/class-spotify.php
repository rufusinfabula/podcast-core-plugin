<?php
/**
 * Episode artwork from Spotify: the horizontal (16:9, 640×360) image Spotify shows for
 * each episode, via the official oEmbed endpoint. The open.spotify.com link of each
 * episode is read from the public Spotify for Creators page (unofficial: if it changes,
 * episodes simply keep the feed image).
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
		$source = (string) get_post_meta( $post_id, 'podcast_core_source_url', true );
		if ( ! preg_match( '#^https://(podcasters|creators)\.spotify\.com/.+-([a-z0-9]{5,10})/?$#', $source, $m ) ) {
			return '';
		}
		$map = self::episode_map( $source );
		$url = $map[ $m[2] ] ?? '';
		if ( $url ) {
			update_post_meta( $post_id, self::META, $url );
		}
		return $url;
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
				'user-agent' => 'Mozilla/5.0 (compatible; Podcast Core Plugin/' . PODCAST_CORE_VERSION . ')',
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
			return new \WP_Error( 'podcast_core_custom_image', __( 'The episode has an image chosen by hand.', 'podcast-core-plugin' ) );
		}
		$url = self::episode_url( $post_id );
		if ( ! $url ) {
			return new \WP_Error( 'podcast_core_no_spotify', __( 'Spotify link of the episode not found.', 'podcast-core-plugin' ) );
		}
		$thumb = self::thumbnail( $url );
		if ( ! $thumb ) {
			return new \WP_Error( 'podcast_core_no_thumbnail', __( 'Spotify did not return an image for this episode.', 'podcast-core-plugin' ) );
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
