<?php
/**
 * Sharing and search engines: meta description, Open Graph and Twitter/X card tags for every
 * front-end page (home, episodes, posts, pages, series, authors, archives, search).
 *
 * Text and image come from the content itself; the home page and pages without their own use
 * Podcast → Settings → Podcast → Sharing (description, image), then the tagline and site icon.
 * Nothing is printed when an SEO plugin is active, so tags are never duplicated.
 *
 * @package PodcastCore
 */

namespace PodcastCore;

defined( 'ABSPATH' ) || exit;

final class Social {

	public static function init(): void {
		add_action( 'wp_head', array( __CLASS__, 'output' ), 5 );
		// Pages get an Excerpt box: their sharing description (page content is often layout).
		add_action( 'init', fn() => add_post_type_support( 'page', 'excerpt' ) );
	}

	/**
	 * An SEO plugin already prints these tags.
	 */
	private static function seo_plugin_active(): bool {
		return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' )
			|| defined( 'AIOSEO_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' ) || class_exists( 'WPSEO_Options' );
	}

	public static function output(): void {
		if ( is_admin() || is_feed() || is_404() || self::seo_plugin_active() || ! apply_filters( 'podcast_core_social_tags', true ) ) {
			return;
		}
		$data = self::data();
		$tags = array(
			array( 'name', 'description', $data['description'] ),
			array( 'property', 'og:locale', str_replace( '-', '_', get_locale() ) ),
			array( 'property', 'og:site_name', get_bloginfo( 'name' ) ),
			array( 'property', 'og:type', $data['type'] ),
			array( 'property', 'og:title', $data['title'] ),
			array( 'property', 'og:description', $data['description'] ),
			array( 'property', 'og:url', $data['url'] ),
		);
		if ( $data['image'] ) {
			$tags[] = array( 'property', 'og:image', $data['image']['url'] );
			if ( $data['image']['width'] ) {
				$tags[] = array( 'property', 'og:image:width', (string) $data['image']['width'] );
				$tags[] = array( 'property', 'og:image:height', (string) $data['image']['height'] );
			}
			if ( $data['image']['alt'] ) {
				$tags[] = array( 'property', 'og:image:alt', $data['image']['alt'] );
			}
		}
		foreach ( $data['article'] as $key => $value ) {
			$tags[] = array( 'property', 'article:' . $key, $value );
		}
		$wide   = $data['image'] && $data['image']['width'] > $data['image']['height'];
		$tags[] = array( 'name', 'twitter:card', $wide ? 'summary_large_image' : 'summary' );
		$tags[] = array( 'name', 'twitter:title', $data['title'] );
		$tags[] = array( 'name', 'twitter:description', $data['description'] );
		if ( $data['image'] ) {
			$tags[] = array( 'name', 'twitter:image', $data['image']['url'] );
		}

		echo "\n<!-- Podcast Core Engine: sharing -->\n";
		foreach ( $tags as list( $attr, $key, $value ) ) {
			if ( '' !== (string) $value ) {
				printf( '<meta %1$s="%2$s" content="%3$s" />' . "\n", $attr, esc_attr( $key ), esc_attr( $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed attribute name.
			}
		}
	}

	/**
	 * Title, description, URL, type, image and article dates of the current page.
	 *
	 * @return array{title:string,description:string,url:string,type:string,image:array|null,article:array}
	 */
	public static function data(): array {
		$default_desc = (string) Settings::get( 'share_description' );
		if ( '' === $default_desc ) {
			$default_desc = (string) get_bloginfo( 'description' );
		}
		$data = array(
			'title'       => wp_strip_all_tags( wp_get_document_title() ),
			'description' => $default_desc,
			'url'         => self::current_url(),
			'type'        => 'website',
			'image'       => null,
			'article'     => array(),
		);

		if ( is_front_page() || is_home() && ! get_option( 'page_for_posts' ) ) {
			$data['title'] = get_bloginfo( 'name' );
		} elseif ( is_singular() ) {
			$post            = get_queried_object();
			$data['title']   = wp_strip_all_tags( get_the_title( $post ) );
			$data['url']     = (string) get_permalink( $post );
			$data['type']    = 'page' === $post->post_type ? 'website' : 'article';
			// Pages: only their excerpt (their content is often layout: buttons, query loops…).
			$text            = has_excerpt( $post ) ? $post->post_excerpt : ( 'page' === $post->post_type ? '' : $post->post_content );
			$data['description'] = self::summary( $text ) ?: $default_desc;
			$data['image']   = self::image( (int) get_post_thumbnail_id( $post ) );
			if ( 'article' === $data['type'] ) {
				$data['article'] = array(
					'published_time' => get_post_time( 'c', true, $post ),
					'modified_time'  => get_post_modified_time( 'c', true, $post ),
				);
			}
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term                = get_queried_object();
			$data['title']       = $term->name;
			$data['url']         = (string) get_term_link( $term );
			$data['description'] = self::summary( (string) $term->description ) ?: $default_desc;
			if ( PODCAST_CORE_SERIES === $term->taxonomy ) {
				$data['image'] = self::series_image( $term );
			}
		} elseif ( is_author() ) {
			$user                = get_queried_object();
			$data['title']       = $user->display_name;
			$data['type']        = 'profile';
			$data['description'] = self::summary( (string) get_the_author_meta( 'description', $user->ID ) ) ?: $default_desc;
			$avatar              = get_avatar_url( $user->ID, array( 'size' => 512 ) );
			// A local profile picture only: Gravatar's placeholder makes a poor preview.
			$data['image']       = $avatar && false === strpos( $avatar, 'gravatar.com' ) ? array( 'url' => html_entity_decode( $avatar ), 'width' => 512, 'height' => 512, 'alt' => $user->display_name ) : null;
		} elseif ( is_post_type_archive() ) {
			$data['title'] = (string) post_type_archive_title( '', false );
		}

		if ( ! $data['image'] ) {
			$data['image'] = self::default_image();
		}
		return $data;
	}

	/**
	 * Plain text, about 155 characters, cut at a word.
	 */
	private static function summary( string $text ): string {
		$text = excerpt_remove_blocks( strip_shortcodes( $text ) );
		$text = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' ) ) );
		if ( mb_strlen( $text ) <= 160 ) {
			return $text;
		}
		$cut = mb_substr( $text, 0, 155 );
		$cut = preg_replace( '/\s+\S*$/u', '', $cut );
		return rtrim( $cut, " ,;:.–-" ) . '…';
	}

	/**
	 * Image data of an attachment (full size), or null.
	 */
	private static function image( int $id ): ?array {
		if ( ! $id ) {
			return null;
		}
		$src = wp_get_attachment_image_src( $id, 'full' );
		if ( ! $src ) {
			return null;
		}
		return array(
			'url'    => $src[0],
			'width'  => (int) $src[1],
			'height' => (int) $src[2],
			'alt'    => trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ),
		);
	}

	/**
	 * Series: image of its latest episode, else the series icon.
	 */
	private static function series_image( \WP_Term $term ): ?array {
		$latest = get_posts(
			array(
				'post_type'      => PODCAST_CORE_EPISODE,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => PODCAST_CORE_SERIES,
						'terms'    => $term->term_id,
					),
				),
			)
		);
		$image = $latest ? self::image( (int) get_post_thumbnail_id( $latest[0] ) ) : null;
		return $image ?: self::image( Series_Icons::get( $term->term_id ) );
	}

	/**
	 * Sharing image from the settings, else the site icon.
	 */
	private static function default_image(): ?array {
		$ids = array_filter( array_map( 'absint', (array) Settings::get( 'share_image' ) ) );
		return self::image( (int) ( $ids ? reset( $ids ) : get_option( 'site_icon' ) ) );
	}

	/**
	 * Address of the current page without query string (pagination kept).
	 */
	private static function current_url(): string {
		global $wp;
		$path = isset( $wp->request ) ? (string) $wp->request : '';
		return home_url( '' === $path ? '/' : user_trailingslashit( $path ) );
	}
}
