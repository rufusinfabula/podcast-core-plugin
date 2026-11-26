<?php
/**
 * Episodes list (Podcast → All episodes): series filter (with "No series"), bulk
 * actions to add / move / remove series, episode number and season columns.
 *
 * @package PodcastCore
 */

namespace PodcastCore;

defined( 'ABSPATH' ) || exit;

final class Admin_List {

	private const SCREEN = 'edit-' . PODCAST_CORE_EPISODE;
	private const NONE   = 'podcast-core-none';

	public static function init(): void {
		add_action( 'restrict_manage_posts', array( __CLASS__, 'filter' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'apply_filter' ) );
		add_filter( 'bulk_actions-' . self::SCREEN, array( __CLASS__, 'bulk_actions' ) );
		add_filter( 'handle_bulk_actions-' . self::SCREEN, array( __CLASS__, 'handle_bulk' ), 10, 3 );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
		add_filter( 'manage_' . PODCAST_CORE_EPISODE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . PODCAST_CORE_EPISODE . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_filter( 'manage_' . self::SCREEN . '_sortable_columns', array( __CLASS__, 'sortable' ) );
	}

	/* ---------- Series filter ---------- */

	public static function filter( string $post_type ): void {
		if ( PODCAST_CORE_EPISODE !== $post_type ) {
			return;
		}
		$current = isset( $_GET[ PODCAST_CORE_SERIES ] ) ? sanitize_title( wp_unslash( $_GET[ PODCAST_CORE_SERIES ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<label class="screen-reader-text" for="podcast-core-series-filter">' . esc_html__( 'Filter by series', 'podcast-core-plugin' ) . '</label>';
		echo '<select name="' . esc_attr( PODCAST_CORE_SERIES ) . '" id="podcast-core-series-filter">';
		printf( '<option value="">%s</option>', esc_html__( 'All series', 'podcast-core-plugin' ) );
		printf( '<option value="%s" %s>%s</option>', esc_attr( self::NONE ), selected( $current, self::NONE, false ), esc_html__( '— No series —', 'podcast-core-plugin' ) );
		foreach ( self::series() as $term ) {
			printf( '<option value="%s" %s>%s (%d)</option>', esc_attr( $term->slug ), selected( $current, $term->slug, false ), esc_html( $term->name ), (int) $term->count );
		}
		echo '</select>';
	}

	public static function apply_filter( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() || PODCAST_CORE_EPISODE !== $query->get( 'post_type' ) ) {
			return;
		}
		if ( self::NONE === $query->get( PODCAST_CORE_SERIES ) ) {
			$query->set( PODCAST_CORE_SERIES, '' );
			$query->set(
				'tax_query',
				array(
					array(
						'taxonomy' => PODCAST_CORE_SERIES,
						'operator' => 'NOT EXISTS',
					),
				)
			);
		}
		$orderby = $query->get( 'orderby' );
		if ( in_array( $orderby, array( 'podcast_core_number', 'podcast_core_season' ), true ) ) {
			$query->set( 'meta_key', 'podcast_core_number' === $orderby ? 'podcast_core_episode_number' : 'podcast_core_season_number' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	/* ---------- Bulk actions ---------- */

	/**
	 * @return \WP_Term[]
	 */
	private static function series(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => PODCAST_CORE_SERIES,
				'hide_empty' => false,
			)
		);
		return is_wp_error( $terms ) ? array() : $terms;
	}

	public static function bulk_actions( array $actions ): array {
		$series = self::series();
		if ( ! $series || ! current_user_can( get_taxonomy( PODCAST_CORE_SERIES )->cap->assign_terms ) ) {
			return $actions;
		}
		// Grouped under a heading (WordPress renders nested arrays as <optgroup>).
		$add  = array();
		$move = array();
		foreach ( $series as $term ) {
			/* translators: %s: series name */
			$add[ 'podcast_core_add_' . $term->term_id ] = sprintf( __( 'Add to “%s”', 'podcast-core-plugin' ), $term->name );
			/* translators: %s: series name */
			$move[ 'podcast_core_move_' . $term->term_id ] = sprintf( __( 'Move to “%s” (only this series)', 'podcast-core-plugin' ), $term->name );
		}
		$actions[ __( 'Series: add', 'podcast-core-plugin' ) ]  = $add;
		$actions[ __( 'Series: move', 'podcast-core-plugin' ) ] = $move;
		$actions[ __( 'Series: remove', 'podcast-core-plugin' ) ] = array( 'podcast_core_remove' => __( 'Remove from all series', 'podcast-core-plugin' ) );
		return $actions;
	}

	public static function handle_bulk( string $redirect, string $action, array $ids ): string {
		if ( ! preg_match( '/^podcast_core_(add|move|remove)(?:_(\d+))?$/', $action, $m ) ) {
			return $redirect;
		}
		if ( ! current_user_can( get_taxonomy( PODCAST_CORE_SERIES )->cap->assign_terms ) ) {
			return $redirect;
		}
		$term_id = isset( $m[2] ) ? (int) $m[2] : 0;
		if ( 'remove' !== $m[1] && ! get_term( $term_id, PODCAST_CORE_SERIES ) ) {
			return $redirect;
		}
		$done = 0;
		foreach ( array_map( 'intval', $ids ) as $id ) {
			if ( PODCAST_CORE_EPISODE !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
				continue;
			}
			if ( 'add' === $m[1] ) {
				$result = wp_add_object_terms( $id, $term_id, PODCAST_CORE_SERIES );
			} elseif ( 'move' === $m[1] ) {
				$result = wp_set_object_terms( $id, array( $term_id ), PODCAST_CORE_SERIES );
			} else {
				$result = wp_set_object_terms( $id, array(), PODCAST_CORE_SERIES );
			}
			if ( ! is_wp_error( $result ) ) {
				++$done;
			}
		}
		return add_query_arg(
			array(
				'podcast_core_series_done' => $done,
				'podcast_core_series_op'   => $m[1],
				'podcast_core_series_term' => $term_id,
			),
			remove_query_arg( array( 'podcast_core_series_done', 'podcast_core_series_op', 'podcast_core_series_term' ), $redirect )
		);
	}

	public static function notice(): void {
		$screen = get_current_screen();
		if ( ! $screen || self::SCREEN !== $screen->id || ! isset( $_GET['podcast_core_series_done'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$done = absint( $_GET['podcast_core_series_done'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$op   = sanitize_key( $_GET['podcast_core_series_op'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$term = get_term( absint( $_GET['podcast_core_series_term'] ?? 0 ), PODCAST_CORE_SERIES ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$name = $term && ! is_wp_error( $term ) ? $term->name : '';
		if ( 'remove' === $op ) {
			/* translators: %d: number of episodes */
			$text = sprintf( _n( '%d episode removed from all series.', '%d episodes removed from all series.', $done, 'podcast-core-plugin' ), $done );
		} elseif ( 'move' === $op ) {
			/* translators: 1: number of episodes, 2: series name */
			$text = sprintf( _n( '%1$d episode moved to “%2$s”.', '%1$d episodes moved to “%2$s”.', $done, 'podcast-core-plugin' ), $done, $name );
		} else {
			/* translators: 1: number of episodes, 2: series name */
			$text = sprintf( _n( '%1$d episode added to “%2$s”.', '%1$d episodes added to “%2$s”.', $done, 'podcast-core-plugin' ), $done, $name );
		}
		printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $text ) );
	}

	/* ---------- Columns ---------- */

	public static function columns( array $columns ): array {
		$out = array();
		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$out['podcast_core_number'] = _x( 'No.', 'episode number column', 'podcast-core-plugin' );
			}
			$out[ $key ] = $label;
			if ( 'title' === $key ) {
				$out['podcast_core_season'] = __( 'Season', 'podcast-core-plugin' );
			}
		}
		return $out;
	}

	public static function column( string $column, int $post_id ): void {
		if ( 'podcast_core_number' === $column ) {
			echo esc_html( (string) ( get_post_meta( $post_id, 'podcast_core_episode_number', true ) ?: '—' ) );
		} elseif ( 'podcast_core_season' === $column ) {
			echo esc_html( (string) ( get_post_meta( $post_id, 'podcast_core_season_number', true ) ?: '—' ) );
		}
	}

	public static function sortable( array $columns ): array {
		$columns['podcast_core_number'] = 'podcast_core_number';
		$columns['podcast_core_season'] = 'podcast_core_season';
		return $columns;
	}
}
