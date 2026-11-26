<?php
/**
 * WP-CLI commands.
 *
 * @package PodcastCore
 */

namespace PodcastCore;

use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Manage Podcast Core content.
 */
final class CLI {

	/**
	 * Converts posts of another post type (e.g. created with CPT UI + ACF) into Podcast Core episodes.
	 *
	 * Old meta values are copied, never deleted, so the migration can be checked before cleaning up.
	 *
	 * ## OPTIONS
	 *
	 * --post-type=<post-type>
	 * : Source post type.
	 *
	 * [--taxonomy=<taxonomy>]
	 * : Source taxonomy whose terms become Series.
	 *
	 * [--meta=<map>]
	 * : Comma-separated old_key:new_field pairs. New fields: episode_number, season_number, audio_url, duration, video_url.
	 *
	 * [--dry-run]
	 * : Show what would change without writing anything.
	 *
	 * ## EXAMPLES
	 *
	 *     wp podcast-core migrate --post-type=puntata --taxonomy=serie --meta=numero_episodio:episode_number,link_mp3_spotify:audio_url,link_video_youtube:video_url --dry-run
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Named args.
	 */
	public function migrate( array $args, array $assoc_args ): void {
		$from_type = $assoc_args['post-type'];
		$from_tax  = $assoc_args['taxonomy'] ?? '';
		$dry_run   = ! empty( $assoc_args['dry-run'] );
		$fields    = array_keys( Episode_Meta::fields() );
		$map       = array();

		foreach ( array_filter( explode( ',', $assoc_args['meta'] ?? '' ) ) as $pair ) {
			list( $old, $new ) = array_map( 'trim', explode( ':', $pair ) + array( '', '' ) );
			if ( ! in_array( $new, $fields, true ) ) {
				WP_CLI::error( sprintf( 'Unknown field "%s". Allowed: %s', $new, implode( ', ', $fields ) ) );
			}
			$map[ $old ] = $new;
		}
		if ( $from_tax && ! taxonomy_exists( $from_tax ) ) {
			WP_CLI::error( sprintf( 'Taxonomy "%s" is not registered; keep the plugin that registers it active during migration.', $from_tax ) );
		}

		$term_map = $from_tax ? $this->migrate_terms( $from_tax, $dry_run ) : array();

		$ids = get_posts(
			array(
				'post_type'      => $from_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		if ( ! $ids ) {
			WP_CLI::warning( sprintf( 'No posts of type "%s" found.', $from_type ) );
		}

		foreach ( $ids as $id ) {
			WP_CLI::log( sprintf( '%s #%d "%s"', $dry_run ? '[dry-run]' : 'Migrating', $id, get_the_title( $id ) ) );
			foreach ( $map as $old => $new ) {
				$value = get_post_meta( $id, $old, true );
				if ( '' === $value || null === $value ) {
					continue;
				}
				WP_CLI::log( sprintf( '    %s → podcast_core_%s = %s', $old, $new, is_scalar( $value ) ? $value : wp_json_encode( $value ) ) );
				if ( ! $dry_run ) {
					update_post_meta( $id, 'podcast_core_' . $new, $value );
				}
			}
			// Change the type first: term counts only include posts of the taxonomy's object types.
			if ( ! $dry_run ) {
				set_post_type( $id, PODCAST_CORE_EPISODE );
			}
			if ( $from_tax ) {
				$old_terms = wp_get_object_terms( $id, $from_tax, array( 'fields' => 'ids' ) );
				$new_terms = array_values( array_filter( array_map( fn( $t ) => $term_map[ $t ] ?? 0, $old_terms ) ) );
				WP_CLI::log( sprintf( '    series: %d term(s)', count( $old_terms ) ) );
				if ( ! $dry_run && $new_terms ) {
					wp_set_object_terms( $id, $new_terms, PODCAST_CORE_SERIES );
				}
			}
		}

		if ( ! $dry_run ) {
			if ( $term_map ) {
				wp_update_term_count_now( array_values( $term_map ), PODCAST_CORE_SERIES );
			}
			flush_rewrite_rules();
		}
		WP_CLI::success( sprintf( '%d post(s) %s.', count( $ids ), $dry_run ? 'would be migrated' : 'migrated' ) );
	}

	/**
	 * Imports episodes from the podcast RSS feed. Existing episodes are never changed.
	 *
	 * ## OPTIONS
	 *
	 * [--url=<url>]
	 * : Feed address. Default: the one in Settings → Podcast.
	 *
	 * [--dry-run]
	 * : Only count new and existing episodes.
	 *
	 * ## EXAMPLES
	 *
	 *     wp podcast-core import --dry-run
	 *     wp podcast-core import
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Named args.
	 */
	public function import( array $args, array $assoc_args ): void {
		if ( ! empty( $assoc_args['url'] ) ) {
			add_filter(
				'option_' . Settings::OPTION,
				function ( $value ) use ( $assoc_args ) {
					$value             = (array) $value;
					$value['feed_url'] = $assoc_args['url'];
					return $value;
				}
			);
		}
		if ( ! empty( $assoc_args['dry-run'] ) ) {
			$preview = Importer::preview();
			if ( is_wp_error( $preview ) ) {
				WP_CLI::error( $preview->get_error_message() );
			}
			WP_CLI::log( sprintf( 'Feed: %s', Importer::feed_url() ) );
			WP_CLI::log( sprintf( 'Episodes in feed: %d — new: %d — already on the site: %d', $preview['total'], $preview['new'], $preview['existing'] ) );
			foreach ( $preview['sample'] as $title ) {
				WP_CLI::log( '  + ' . $title );
			}
			return;
		}
		add_action( 'podcast_core_episode_imported', fn( $id, $e ) => WP_CLI::log( sprintf( '#%d %s', $id, $e['title'] ) ), 10, 2 );
		$result = Importer::sync( 'cli' );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}
		foreach ( $result['errors'] as $error ) {
			WP_CLI::warning( $error );
		}
		WP_CLI::success( sprintf( '%d imported, %d already on the site.', $result['created'], $result['existing'] ) );
	}

	/**
	 * Uses the horizontal cover Spotify shows for each episode as featured image.
	 *
	 * Only episodes whose image came from the feed are changed, unless --force.
	 * Feed images no longer used are deleted at the end.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Also replace images chosen by hand.
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Named args.
	 */
	public function images( array $args, array $assoc_args ): void {
		$force = ! empty( $assoc_args['force'] );
		$ids   = $force ? get_posts(
			array(
				'post_type'      => PODCAST_CORE_EPISODE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		) : Spotify::pending();
		$done  = 0;
		foreach ( $ids as $id ) {
			$result = Spotify::apply_image( $id, $force );
			if ( is_wp_error( $result ) ) {
				WP_CLI::warning( sprintf( '#%d %s: %s', $id, get_the_title( $id ), $result->get_error_message() ) );
			} else {
				++$done;
				WP_CLI::log( sprintf( '#%d %s', $id, get_the_title( $id ) ) );
			}
		}
		$deleted = Spotify::delete_unused_feed_images();
		WP_CLI::success( sprintf( '%d images updated, %d unused feed images deleted.', $done, $deleted ) );
	}

	/**
	 * Copies terms (keeping slug, description and hierarchy). Returns old_id => new_id.
	 */
	private function migrate_terms( string $from_tax, bool $dry_run ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $from_tax,
				'hide_empty' => false,
				'orderby'    => 'parent',
			)
		);
		$map = array();
		foreach ( $terms as $term ) {
			WP_CLI::log( sprintf( '%s series "%s" (%s)', $dry_run ? '[dry-run] Create' : 'Create', $term->name, $term->slug ) );
			if ( $dry_run ) {
				continue;
			}
			$existing = get_term_by( 'slug', $term->slug, PODCAST_CORE_SERIES );
			if ( $existing ) {
				$map[ $term->term_id ] = $existing->term_id;
				continue;
			}
			$result = wp_insert_term(
				$term->name,
				PODCAST_CORE_SERIES,
				array(
					'slug'        => $term->slug,
					'description' => $term->description,
					'parent'      => $term->parent ? ( $map[ $term->parent ] ?? 0 ) : 0,
				)
			);
			if ( is_wp_error( $result ) ) {
				WP_CLI::warning( $result->get_error_message() );
				continue;
			}
			$map[ $term->term_id ] = $result['term_id'];
		}
		return $map;
	}
}
