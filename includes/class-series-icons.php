<?php
/**
 * Series icons: an optional image per series (and sub-series), picked from the Media Library.
 * Shown by the Series list block and available to themes via Series_Icons::get().
 *
 * @package PodcastCore
 */

namespace PodcastCore;

defined( 'ABSPATH' ) || exit;

final class Series_Icons {

	public const META = 'podcast_core_icon';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( PODCAST_CORE_SERIES . '_add_form_fields', array( __CLASS__, 'field_add' ) );
		add_action( PODCAST_CORE_SERIES . '_edit_form_fields', array( __CLASS__, 'field_edit' ) );
		add_action( 'created_' . PODCAST_CORE_SERIES, array( __CLASS__, 'save' ) );
		add_action( 'edited_' . PODCAST_CORE_SERIES, array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'manage_edit-' . PODCAST_CORE_SERIES . '_columns', array( __CLASS__, 'columns' ) );
		add_filter( 'manage_' . PODCAST_CORE_SERIES . '_custom_column', array( __CLASS__, 'column' ), 10, 3 );
	}

	public static function register_meta(): void {
		register_term_meta(
			PODCAST_CORE_SERIES,
			self::META,
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
			)
		);
	}

	/**
	 * Attachment ID of the series icon, 0 if none.
	 */
	public static function get( int $term_id ): int {
		$id = (int) get_term_meta( $term_id, self::META, true );
		return $id && wp_attachment_is_image( $id ) ? $id : 0;
	}

	public static function assets( string $hook ): void {
		$screen = get_current_screen();
		if ( ! $screen || PODCAST_CORE_SERIES !== $screen->taxonomy || ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'podcast-core-series-icon', PODCAST_CORE_URL . 'assets/js/series-icon.js', array( 'jquery' ), PODCAST_CORE_VERSION, true );
		wp_localize_script(
			'podcast-core-series-icon',
			'podcastCoreSeriesIcon',
			array(
				'title'  => __( 'Series icon', 'podcast-core-engine' ),
				'button' => __( 'Use as icon', 'podcast-core-engine' ),
			)
		);
	}

	private static function picker( int $id ): string {
		$preview = $id ? wp_get_attachment_image( $id, 'thumbnail', false, array( 'style' => 'width:64px;height:64px;object-fit:contain;' ) ) : '';
		return sprintf(
			'<div class="podcast-core-series-icon">%5$s<span class="podcast-core-series-icon__preview">%1$s</span>' .
			'<input type="hidden" name="podcast_core_icon" value="%2$s"> ' .
			'<button type="button" class="button podcast-core-series-icon__choose">%3$s</button> ' .
			'<button type="button" class="button-link podcast-core-series-icon__remove"%6$s>%4$s</button></div>',
			$preview,
			esc_attr( $id ? (string) $id : '' ),
			esc_html__( 'Choose image', 'podcast-core-engine' ),
			esc_html__( 'Remove', 'podcast-core-engine' ),
			wp_nonce_field( 'podcast_core_series_icon', 'podcast_core_series_icon_nonce', true, false ),
			$id ? '' : ' hidden'
		);
	}

	public static function field_add(): void {
		printf(
			'<div class="form-field"><label>%1$s</label>%2$s<p>%3$s</p></div>',
			esc_html__( 'Icon', 'podcast-core-engine' ),
			self::picker( 0 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in picker().
			esc_html__( 'Optional. A small square image shown next to the series name, for example in the Series list block.', 'podcast-core-engine' )
		);
	}

	public static function field_edit( \WP_Term $term ): void {
		printf(
			'<tr class="form-field"><th scope="row"><label>%1$s</label></th><td>%2$s<p class="description">%3$s</p></td></tr>',
			esc_html__( 'Icon', 'podcast-core-engine' ),
			self::picker( self::get( $term->term_id ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in picker().
			esc_html__( 'Optional. A small square image shown next to the series name, for example in the Series list block.', 'podcast-core-engine' )
		);
	}

	public static function save( int $term_id ): void {
		if ( ! isset( $_POST['podcast_core_series_icon_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['podcast_core_series_icon_nonce'] ), 'podcast_core_series_icon' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		$id = absint( $_POST['podcast_core_icon'] ?? 0 );
		if ( $id && wp_attachment_is_image( $id ) ) {
			update_term_meta( $term_id, self::META, $id );
		} else {
			delete_term_meta( $term_id, self::META );
		}
	}

	public static function columns( array $columns ): array {
		$out = array();
		foreach ( $columns as $key => $label ) {
			if ( 'name' === $key ) {
				$out['podcast_core_icon'] = __( 'Icon', 'podcast-core-engine' );
			}
			$out[ $key ] = $label;
		}
		return $out;
	}

	public static function column( string $content, string $column, int $term_id ): string {
		if ( 'podcast_core_icon' !== $column ) {
			return $content;
		}
		$id = self::get( $term_id );
		return $id ? wp_get_attachment_image( $id, array( 40, 40 ), false, array( 'style' => 'width:40px;height:40px;object-fit:contain;' ) ) : '';
	}
}
