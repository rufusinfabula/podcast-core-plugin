<?php
/**
 * schema.org PodcastEpisode structured data on single episode pages.
 *
 * @package PodcastCore
 */

namespace PodcastCore;

defined( 'ABSPATH' ) || exit;

final class Schema {

	public static function init(): void {
		add_action( 'wp_head', array( __CLASS__, 'output' ) );
	}

	public static function output(): void {
		if ( ! is_singular( PODCAST_CORE_EPISODE ) ) {
			return;
		}
		$data = self::episode( get_queried_object_id() );
		if ( $data ) {
			printf( "<script type=\"application/ld+json\">%s</script>\n", wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
		}
	}

	/**
	 * Builds the PodcastEpisode object for a post. Filterable via `podcast_core_schema_episode`.
	 */
	public static function episode( int $post_id ): array {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}
		$show = Settings::get( 'show_name' );
		$data = array(
			'@context'      => 'https://schema.org',
			'@type'         => 'PodcastEpisode',
			'name'          => self::plain( get_the_title( $post ) ),
			'url'           => get_permalink( $post ),
			'datePublished' => get_the_date( 'c', $post ),
			'description'   => self::plain( get_the_excerpt( $post ) ),
			'partOfSeries'  => array(
				'@type' => 'PodcastSeries',
				'name'  => self::plain( $show ? $show : get_bloginfo( 'name' ) ),
				'url'   => home_url( '/' ),
			),
		);

		$number = podcast_core_get_field( 'episode_number', $post );
		if ( $number ) {
			$data['episodeNumber'] = (int) $number;
		}
		$season = podcast_core_get_field( 'season_number', $post );
		if ( $season ) {
			$data['partOfSeason'] = array(
				'@type'        => 'PodcastSeason',
				'seasonNumber' => (int) $season,
			);
		}
		$audio = podcast_core_get_field( 'audio_url', $post );
		if ( $audio ) {
			$media   = array(
				'@type'      => 'AudioObject',
				'contentUrl' => $audio,
			);
			$seconds = podcast_core_duration_to_seconds( (string) podcast_core_get_field( 'duration', $post ) );
			if ( $seconds ) {
				$media['duration'] = sprintf( 'PT%dH%dM%dS', intdiv( $seconds, 3600 ), intdiv( $seconds % 3600, 60 ), $seconds % 60 );
			}
			$data['associatedMedia'] = $media;
		}
		$image = get_the_post_thumbnail_url( $post, 'large' );
		if ( $image ) {
			$data['image'] = $image;
		}

		return (array) apply_filters( 'podcast_core_schema_episode', $data, $post );
	}

	/**
	 * Plain text for JSON-LD: no tags, no HTML entities (e.g. &#8217; from wptexturize).
	 */
	private static function plain( string $text ): string {
		return trim( html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}
}
