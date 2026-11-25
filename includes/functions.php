<?php
/**
 * Public template functions. Safe to call from themes.
 *
 * @package PodcastCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post type and taxonomy names.
 */
const PODCAST_CORE_EPISODE = 'podcast_episode';
const PODCAST_CORE_SERIES  = 'podcast_series';

/**
 * Returns one episode field.
 *
 * @param string           $field One of: episode_number, season_number, audio_url, duration, video_url.
 * @param int|WP_Post|null $post  Post (defaults to the current one).
 * @return mixed Field value, or empty string when unset.
 */
function podcast_core_get_field( string $field, $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	return get_post_meta( $post->ID, 'podcast_core_' . $field, true );
}

/**
 * Converts "HH:MM:SS", "MM:SS" or plain seconds to a number of seconds.
 */
function podcast_core_duration_to_seconds( string $duration ): int {
	$duration = trim( $duration );
	if ( '' === $duration ) {
		return 0;
	}
	if ( ctype_digit( $duration ) ) {
		return (int) $duration;
	}
	$parts   = array_reverse( array_map( 'intval', explode( ':', $duration ) ) );
	$seconds = 0;
	foreach ( $parts as $i => $value ) {
		$seconds += $value * ( 60 ** $i );
	}
	return $seconds;
}

/**
 * Returns a plugin setting.
 *
 * @param string $key Setting key.
 * @return mixed
 */
function podcast_core_get_setting( string $key ) {
	return PodcastCore\Settings::get( $key );
}
