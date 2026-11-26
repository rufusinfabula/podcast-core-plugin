<?php
/**
 * Reads a podcast RSS feed into plain episode arrays.
 *
 * @package PodcastCore
 */

namespace PodcastCore;

defined( 'ABSPATH' ) || exit;

final class Feed {

	private const ITUNES  = 'http://www.itunes.com/dtds/podcast-1.0.dtd';
	private const CONTENT = 'http://purl.org/rss/1.0/modules/content/';

	/**
	 * Downloads and parses a feed.
	 *
	 * @return array|\WP_Error List of episodes, oldest first.
	 */
	public static function fetch( string $url ) {
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'    => 30,
				'user-agent' => 'Podcast Core Plugin/' . PODCAST_CORE_VERSION . '; ' . home_url( '/' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			/* translators: %d: HTTP status code */
			return new \WP_Error( 'podcast_core_feed_http', sprintf( __( 'The feed answered with HTTP status %d.', 'podcast-core-plugin' ), $code ) );
		}
		return self::parse( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * @return array|\WP_Error
	 */
	public static function parse( string $xml ) {
		$previous = libxml_use_internal_errors( true );
		$rss      = simplexml_load_string( $xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET );
		libxml_use_internal_errors( $previous );
		if ( ! $rss || ! isset( $rss->channel ) ) {
			return new \WP_Error( 'podcast_core_feed_invalid', __( 'This address does not contain a valid RSS feed.', 'podcast-core-plugin' ) );
		}

		$episodes = array();
		foreach ( $rss->channel->item as $item ) {
			$itunes  = $item->children( self::ITUNES );
			$content = $item->children( self::CONTENT );
			$audio   = isset( $item->enclosure ) ? (string) $item->enclosure['url'] : '';
			$guid    = trim( (string) $item->guid );
			$guid    = '' !== $guid ? $guid : ( $audio ? $audio : (string) $item->link );
			if ( '' === $guid ) {
				continue;
			}
			$image = isset( $itunes->image ) ? (string) $itunes->image->attributes()->href : '';
			$html  = (string) $content->encoded;
			$html  = '' !== trim( $html ) ? $html : (string) $item->description;

			$episodes[] = array(
				'guid'           => $guid,
				'title'          => trim( (string) $item->title ),
				'html'           => $html,
				'date_gmt'       => self::date_gmt( (string) $item->pubDate ),
				'audio_url'      => $audio,
				'duration'       => trim( (string) $itunes->duration ),
				'episode_number' => absint( (string) $itunes->episode ),
				'season_number'  => absint( (string) $itunes->season ),
				'type'           => trim( (string) $itunes->episodeType ),
				'image_url'      => $image,
				'source_url'     => trim( (string) $item->link ),
			);
		}
		// Feeds list newest first; import oldest first so IDs follow publication order.
		return array_reverse( $episodes );
	}

	private static function date_gmt( string $date ): string {
		$time = strtotime( $date );
		return $time ? gmdate( 'Y-m-d H:i:s', $time ) : current_time( 'mysql', true );
	}
}
