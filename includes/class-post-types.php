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
	}

	/**
	 * Episodes per page on the episode archive and series pages (Settings → Podcast).
	 */
	public static function per_page( \WP_Query $query ): void {
		$per_page = (int) Settings::get( 'per_page' );
		if ( $per_page < 1 || is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( $query->is_post_type_archive( PODCAST_CORE_EPISODE ) || $query->is_tax( PODCAST_CORE_SERIES ) ) {
			$query->set( 'posts_per_page', $per_page );
		}
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
