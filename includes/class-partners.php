<?php
/**
 * Partners: sponsors, supporters, partners and friends of the podcast.
 *
 * Each partner is a small private post (not a public page): name = title, short text = excerpt,
 * logo = featured image, website = podcast_core_partner_url, order = menu order.
 * Partner types (taxonomy) let a site show, for example, only sponsors in one section.
 *
 * @package PodcastCore
 */

namespace PodcastCore;

defined( 'ABSPATH' ) || exit;

final class Partners {

	public const URL_META = 'podcast_core_partner_url';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'classic_editor' ), 10, 2 );
		add_action( 'add_meta_boxes_' . PODCAST_CORE_PARTNER, array( __CLASS__, 'meta_boxes' ) );
		add_action( 'save_post_' . PODCAST_CORE_PARTNER, array( __CLASS__, 'save' ) );
		add_filter( 'enter_title_here', array( __CLASS__, 'title_placeholder' ), 10, 2 );
		add_filter( 'manage_' . PODCAST_CORE_PARTNER . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . PODCAST_CORE_PARTNER . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_action( 'pre_get_posts', array( __CLASS__, 'admin_order' ) );
	}

	public static function register(): void {
		register_post_type(
			PODCAST_CORE_PARTNER,
			array(
				'labels'             => array(
					'name'                  => __( 'Partners', 'podcast-core-engine' ),
					'singular_name'         => __( 'Partner', 'podcast-core-engine' ),
					'all_items'             => __( 'Partners', 'podcast-core-engine' ),
					'add_new_item'          => __( 'Add new partner', 'podcast-core-engine' ),
					'add_new'               => __( 'Add partner', 'podcast-core-engine' ),
					'edit_item'             => __( 'Edit partner', 'podcast-core-engine' ),
					'new_item'              => __( 'New partner', 'podcast-core-engine' ),
					'search_items'          => __( 'Search partners', 'podcast-core-engine' ),
					'not_found'             => __( 'No partners found.', 'podcast-core-engine' ),
					'not_found_in_trash'    => __( 'No partners found in Trash.', 'podcast-core-engine' ),
					'featured_image'        => __( 'Logo', 'podcast-core-engine' ),
					'set_featured_image'    => __( 'Set logo', 'podcast-core-engine' ),
					'remove_featured_image' => __( 'Remove logo', 'podcast-core-engine' ),
					'use_featured_image'    => __( 'Use as logo', 'podcast-core-engine' ),
				),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => 'edit.php?post_type=' . PODCAST_CORE_EPISODE,
				'show_in_rest'       => true,
				'supports'           => array( 'title', 'excerpt', 'thumbnail', 'page-attributes' ),
				'taxonomies'         => array( PODCAST_CORE_PARTNER_TYPE ),
			)
		);

		register_taxonomy(
			PODCAST_CORE_PARTNER_TYPE,
			PODCAST_CORE_PARTNER,
			array(
				'labels'            => array(
					'name'          => __( 'Partner types', 'podcast-core-engine' ),
					'singular_name' => __( 'Partner type', 'podcast-core-engine' ),
					'all_items'     => __( 'All types', 'podcast-core-engine' ),
					'edit_item'     => __( 'Edit type', 'podcast-core-engine' ),
					'add_new_item'  => __( 'Add new type', 'podcast-core-engine' ),
					'new_item_name' => __( 'New type name', 'podcast-core-engine' ),
					'search_items'  => __( 'Search types', 'podcast-core-engine' ),
					'not_found'     => __( 'No types found.', 'podcast-core-engine' ),
					'back_to_items' => __( '&larr; Back to types', 'podcast-core-engine' ),
				),
				'description'       => __( 'For example Partner, Sponsor, Supporter, Friends. A Partners block can show one type only.', 'podcast-core-engine' ),
				'public'            => false,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => false,
			)
		);

		register_post_meta(
			PODCAST_CORE_PARTNER,
			self::URL_META,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
				'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
			)
		);
	}

	/**
	 * A partner is a few fields: the classic screen is quicker than the block editor.
	 */
	public static function classic_editor( bool $use, string $post_type ): bool {
		return PODCAST_CORE_PARTNER === $post_type ? false : $use;
	}

	public static function title_placeholder( string $text, \WP_Post $post ): string {
		return PODCAST_CORE_PARTNER === $post->post_type ? __( 'Partner name', 'podcast-core-engine' ) : $text;
	}

	public static function meta_boxes(): void {
		add_meta_box( 'podcast-core-partner-url', __( 'Website', 'podcast-core-engine' ), array( __CLASS__, 'url_box' ), PODCAST_CORE_PARTNER, 'normal', 'high' );
		// The excerpt box is the partner's short text.
		remove_meta_box( 'postexcerpt', PODCAST_CORE_PARTNER, 'normal' );
		add_meta_box( 'postexcerpt', __( 'Short text', 'podcast-core-engine' ), array( __CLASS__, 'text_box' ), PODCAST_CORE_PARTNER, 'normal', 'high' );
	}

	public static function url_box( \WP_Post $post ): void {
		wp_nonce_field( 'podcast_core_partner', 'podcast_core_partner_nonce' );
		printf(
			'<p><label class="screen-reader-text" for="podcast_core_partner_url">%1$s</label><input type="url" class="widefat" id="podcast_core_partner_url" name="podcast_core_partner_url" value="%2$s" placeholder="https://"></p><p class="description">%3$s</p>',
			esc_html__( 'Website', 'podcast-core-engine' ),
			esc_attr( (string) get_post_meta( $post->ID, self::URL_META, true ) ),
			esc_html__( 'The logo and the name link here (opens in a new tab).', 'podcast-core-engine' )
		);
	}

	public static function text_box( \WP_Post $post ): void {
		printf(
			'<label class="screen-reader-text" for="excerpt">%1$s</label><textarea rows="3" cols="40" name="excerpt" id="excerpt" class="widefat">%2$s</textarea><p class="description">%3$s</p>',
			esc_html__( 'Short text', 'podcast-core-engine' ),
			esc_textarea( $post->post_excerpt ),
			esc_html__( 'One or two sentences about the partner. The logo is the "Logo" box in the sidebar; the order is "Order" under Attributes.', 'podcast-core-engine' )
		);
	}

	public static function save( int $post_id ): void {
		if ( ! isset( $_POST['podcast_core_partner_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['podcast_core_partner_nonce'] ), 'podcast_core_partner' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$url = esc_url_raw( wp_unslash( $_POST['podcast_core_partner_url'] ?? '' ) );
		if ( $url ) {
			update_post_meta( $post_id, self::URL_META, $url );
		} else {
			delete_post_meta( $post_id, self::URL_META );
		}
	}

	public static function columns( array $columns ): array {
		$out = array();
		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$out['podcast_core_logo'] = __( 'Logo', 'podcast-core-engine' );
			}
			$out[ $key ] = $label;
			if ( 'title' === $key ) {
				$out['podcast_core_url']   = __( 'Website', 'podcast-core-engine' );
				$out['podcast_core_order'] = __( 'Order', 'podcast-core-engine' );
			}
		}
		unset( $out['date'] );
		return $out;
	}

	public static function column( string $column, int $post_id ): void {
		if ( 'podcast_core_logo' === $column ) {
			echo get_the_post_thumbnail( $post_id, array( 60, 60 ), array( 'style' => 'width:60px;height:60px;object-fit:contain;' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( 'podcast_core_url' === $column ) {
			$url = (string) get_post_meta( $post_id, self::URL_META, true );
			if ( $url ) {
				printf( '<a href="%1$s" target="_blank" rel="noopener">%2$s</a>', esc_url( $url ), esc_html( wp_parse_url( $url, PHP_URL_HOST ) ?: $url ) );
			}
		} elseif ( 'podcast_core_order' === $column ) {
			echo (int) get_post_field( 'menu_order', $post_id );
		}
	}

	/**
	 * Admin list in the same order as on the site.
	 */
	public static function admin_order( \WP_Query $query ): void {
		if ( is_admin() && $query->is_main_query() && PODCAST_CORE_PARTNER === $query->get( 'post_type' ) && ! $query->get( 'orderby' ) ) {
			$query->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
		}
	}

	/**
	 * Published partners, in their order.
	 *
	 * @param string $type Partner type slug, or '' for all.
	 * @return \WP_Post[]
	 */
	public static function get( string $type = '', int $limit = -1 ): array {
		$args = array(
			'post_type'      => PODCAST_CORE_PARTNER,
			'post_status'    => 'publish',
			'posts_per_page' => $limit > 0 ? $limit : -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'no_found_rows'  => true,
		);
		if ( '' !== $type ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => PODCAST_CORE_PARTNER_TYPE,
					'field'    => 'slug',
					'terms'    => $type,
				),
			);
		}
		return get_posts( $args );
	}
}
