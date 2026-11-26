<?php
/**
 * Episode fields, stored as native post meta (no ACF needed).
 *
 * @package PodcastCore
 */

namespace PodcastCore;

defined( 'ABSPATH' ) || exit;

final class Episode_Meta {

	/**
	 * Field definitions: name => [type, sanitize callback].
	 * Stored as `podcast_core_{name}`.
	 */
	public static function fields(): array {
		return array(
			'episode_number' => array( 'integer', 'absint' ),
			'season_number'  => array( 'integer', 'absint' ),
			'audio_url'      => array( 'string', 'esc_url_raw' ),
			'duration'       => array( 'string', array( __CLASS__, 'sanitize_duration' ) ),
			'video_url'      => array( 'string', 'esc_url_raw' ),
			'source_url'     => array( 'string', 'esc_url_raw' ),
		);
	}

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	public static function register(): void {
		// Feed identifier of imported episodes (not editable).
		register_post_meta(
			PODCAST_CORE_EPISODE,
			'podcast_core_guid',
			array(
				'type'          => 'string',
				'single'        => true,
				'show_in_rest'  => false,
				'auth_callback' => '__return_false',
			)
		);
		foreach ( self::fields() as $name => list( $type, $sanitize ) ) {
			register_post_meta(
				PODCAST_CORE_EPISODE,
				'podcast_core_' . $name,
				array(
					'type'              => $type,
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => $sanitize,
					'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', $post_id );
					},
				)
			);
		}
	}

	/**
	 * Accepts "HH:MM:SS", "MM:SS" or seconds; anything else is dropped.
	 */
	public static function sanitize_duration( $value ): string {
		$value = trim( (string) $value );
		return preg_match( '/^(\d+:)?\d{1,2}:\d{2}$|^\d+$/', $value ) ? $value : '';
	}
}
