<?php
/**
 * Episode post type and Series taxonomy.
 *
 * @package PodcastCore
 */

namespace PodcastCore;

defined( 'ABSPATH' ) || exit;

final class Post_Types {

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'per_page' ) );
		add_action( 'init', array( __CLASS__, 'register_series_meta' ) );
		add_action( PODCAST_CORE_SERIES . '_add_form_fields', array( __CLASS__, 'series_order_field_add' ) );
		add_action( PODCAST_CORE_SERIES . '_edit_form_fields', array( __CLASS__, 'series_order_field_edit' ) );
		add_action( 'created_' . PODCAST_CORE_SERIES, array( __CLASS__, 'save_series_order' ) );
		add_action( 'edited_' . PODCAST_CORE_SERIES, array( __CLASS__, 'save_series_order' ) );
	}

	/**
	 * Order of a series: its own setting, or the general one.
	 */
	public static function series_order( int $term_id ): string {
		$order = (string) get_term_meta( $term_id, 'podcast_core_order', true );
		return in_array( $order, array( 'asc', 'desc' ), true ) ? $order : (string) Settings::get( 'order' );
	}

	public static function register_series_meta(): void {
		register_term_meta(
			PODCAST_CORE_SERIES,
			'podcast_core_order',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => fn( $v ) => in_array( $v, array( 'asc', 'desc' ), true ) ? $v : '',
			)
		);
	}

	private static function order_select( string $current ): string {
		$options = array(
			''     => __( 'As in Settings → Podcast', 'podcast-core-plugin' ),
			'desc' => __( 'Newest first', 'podcast-core-plugin' ),
			'asc'  => __( 'Oldest first', 'podcast-core-plugin' ),
		);
		$html    = '<select name="podcast_core_order" id="podcast_core_order">';
		foreach ( $options as $value => $label ) {
			$html .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $value ), selected( $current, $value, false ), esc_html( $label ) );
		}
		return $html . '</select>';
	}

	public static function series_order_field_add(): void {
		wp_nonce_field( 'podcast_core_series_order', 'podcast_core_series_order_nonce' );
		printf(
			'<div class="form-field"><label for="podcast_core_order">%s</label>%s<p>%s</p></div>',
			esc_html__( 'Episode order', 'podcast-core-plugin' ),
			self::order_select( '' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in order_select().
			esc_html__( 'Order of the episodes on this series page.', 'podcast-core-plugin' )
		);
	}

	public static function series_order_field_edit( \WP_Term $term ): void {
		wp_nonce_field( 'podcast_core_series_order', 'podcast_core_series_order_nonce' );
		printf(
			'<tr class="form-field"><th scope="row"><label for="podcast_core_order">%s</label></th><td>%s<p class="description">%s</p></td></tr>',
			esc_html__( 'Episode order', 'podcast-core-plugin' ),
			self::order_select( (string) get_term_meta( $term->term_id, 'podcast_core_order', true ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Order of the episodes on this series page.', 'podcast-core-plugin' )
		);
	}

	public static function save_series_order( int $term_id ): void {
		if ( ! isset( $_POST['podcast_core_series_order_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['podcast_core_series_order_nonce'] ), 'podcast_core_series_order' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		$order = sanitize_key( wp_unslash( $_POST['podcast_core_order'] ?? '' ) );
		if ( in_array( $order, array( 'asc', 'desc' ), true ) ) {
			update_term_meta( $term_id, 'podcast_core_order', $order );
		} else {
			delete_term_meta( $term_id, 'podcast_core_order' );
		}
	}

	/**
	 * Episodes per page and order on the episode archive and series pages (Settings → Podcast;
	 * series can override the order).
	 */
	public static function per_page( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		$archive = $query->is_post_type_archive( PODCAST_CORE_EPISODE );
		$series  = $query->is_tax( PODCAST_CORE_SERIES );
		if ( ! $archive && ! $series ) {
			return;
		}
		$per_page = (int) Settings::get( 'per_page' );
		if ( $per_page > 0 ) {
			$query->set( 'posts_per_page', $per_page );
		}
		$order = (string) Settings::get( 'order' );
		if ( $series ) {
			$term = get_term_by( 'slug', (string) $query->get( PODCAST_CORE_SERIES ), PODCAST_CORE_SERIES );
			if ( $term ) {
				$order = self::series_order( $term->term_id );
			}
		}
		$query->set( 'orderby', 'date' );
		$query->set( 'order', 'asc' === $order ? 'ASC' : 'DESC' );
	}

	public static function register(): void {
		$episodes_slug = Settings::get( 'episodes_slug' );

		register_post_type(
			PODCAST_CORE_EPISODE,
			array(
				'labels'        => array(
					'name'               => __( 'Episodes', 'podcast-core-plugin' ),
					'singular_name'      => __( 'Episode', 'podcast-core-plugin' ),
					'menu_name'          => __( 'Podcast', 'podcast-core-plugin' ),
					'all_items'          => __( 'All episodes', 'podcast-core-plugin' ),
					'add_new_item'       => __( 'Add new episode', 'podcast-core-plugin' ),
					'add_new'            => __( 'Add episode', 'podcast-core-plugin' ),
					'edit_item'          => __( 'Edit episode', 'podcast-core-plugin' ),
					'new_item'           => __( 'New episode', 'podcast-core-plugin' ),
					'view_item'          => __( 'View episode', 'podcast-core-plugin' ),
					'view_items'         => __( 'View episodes', 'podcast-core-plugin' ),
					'search_items'       => __( 'Search episodes', 'podcast-core-plugin' ),
					'not_found'          => __( 'No episodes found.', 'podcast-core-plugin' ),
					'not_found_in_trash' => __( 'No episodes found in Trash.', 'podcast-core-plugin' ),
					'archives'           => __( 'Episode archives', 'podcast-core-plugin' ),
					'items_list'         => __( 'Episodes list', 'podcast-core-plugin' ),
				),
				'public'        => true,
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-microphone',
				'menu_position' => 5,
				'has_archive'   => $episodes_slug,
				'rewrite'       => array(
					'slug'       => $episodes_slug,
					'with_front' => false,
				),
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'custom-fields', 'revisions' ),
				'taxonomies'    => array( PODCAST_CORE_SERIES ),
			)
		);

		register_taxonomy(
			PODCAST_CORE_SERIES,
			PODCAST_CORE_EPISODE,
			array(
				'labels'            => array(
					'name'          => __( 'Series', 'podcast-core-plugin' ),
					'singular_name' => __( 'Series', 'podcast-core-plugin' ),
					'all_items'     => __( 'All series', 'podcast-core-plugin' ),
					'edit_item'     => __( 'Edit series', 'podcast-core-plugin' ),
					'view_item'     => __( 'View series', 'podcast-core-plugin' ),
					'add_new_item'  => __( 'Add new series', 'podcast-core-plugin' ),
					'new_item_name' => __( 'New series name', 'podcast-core-plugin' ),
					'search_items'  => __( 'Search series', 'podcast-core-plugin' ),
					'not_found'     => __( 'No series found.', 'podcast-core-plugin' ),
					'back_to_items' => __( '&larr; Back to series', 'podcast-core-plugin' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'       => Settings::get( 'series_slug' ),
					'with_front' => false,
				),
			)
		);
	}
}
