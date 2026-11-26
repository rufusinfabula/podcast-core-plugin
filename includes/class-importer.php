<?php
/**
 * Imports episodes from the podcast RSS feed. Existing episodes (same guid) are never
 * overwritten, so local edits are safe; only new episodes are created.
 *
 * @package PodcastCore
 */

namespace PodcastCore;

defined( 'ABSPATH' ) || exit;

final class Importer {

	public const CRON_HOOK  = 'podcast_core_feed_sync';
	public const STATUS_OPT = 'podcast_core_import_status';
	private const LOCK      = 'podcast_core_import_lock';

	public static function init(): void {
		add_action( self::CRON_HOOK, array( __CLASS__, 'cron_sync' ) );
		add_action( 'init', array( __CLASS__, 'schedule' ) );
	}

	/**
	 * Keeps the hourly check scheduled only while automatic updates are on.
	 */
	public static function schedule(): void {
		$wanted    = Settings::get( 'auto_sync' ) && self::feed_url();
		$scheduled = wp_next_scheduled( self::CRON_HOOK );
		if ( $wanted && ! $scheduled ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'hourly', self::CRON_HOOK );
		} elseif ( ! $wanted && $scheduled ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
		}
	}

	public static function feed_url(): string {
		$url = (string) Settings::get( 'feed_url' );
		return '' !== $url ? $url : (string) Settings::get( 'rss_url' );
	}

	public static function cron_sync(): void {
		self::sync( 'auto' );
	}

	/**
	 * Imports every episode of the feed that is not on the site yet.
	 *
	 * @param string $trigger auto | manual | cli.
	 * @return array|\WP_Error Result summary.
	 */
	public static function sync( string $trigger = 'manual', ?int $limit = null ) {
		$url = self::feed_url();
		if ( '' === $url ) {
			return new \WP_Error( 'podcast_core_no_feed', __( 'Set the feed address in Settings → Podcast first.', 'podcast-core-plugin' ) );
		}
		if ( get_transient( self::LOCK ) ) {
			return new \WP_Error( 'podcast_core_locked', __( 'An import is already running. Try again in a few minutes.', 'podcast-core-plugin' ) );
		}
		set_transient( self::LOCK, 1, 30 * MINUTE_IN_SECONDS );

		$episodes = Feed::fetch( $url );
		if ( is_wp_error( $episodes ) ) {
			delete_transient( self::LOCK );
			self::save_status( $trigger, array( 'error' => $episodes->get_error_message() ) );
			return $episodes;
		}

		$result = array(
			'total'    => count( $episodes ),
			'created'  => 0,
			'existing' => 0,
			'errors'   => array(),
			'pending'  => 0,
		);
		foreach ( $episodes as $episode ) {
			if ( self::find( $episode ) ) {
				++$result['existing'];
				continue;
			}
			if ( null !== $limit && $result['created'] >= $limit ) {
				++$result['pending'];
				continue;
			}
			set_transient( self::LOCK, 1, 30 * MINUTE_IN_SECONDS ); // Keep the lock while images are processed.
			$id = self::create( $episode );
			if ( is_wp_error( $id ) ) {
				$result['errors'][] = $episode['title'] . ': ' . $id->get_error_message();
			} else {
				++$result['created'];
			}
		}

		delete_transient( self::LOCK );
		self::save_status( $trigger, $result );
		return $result;
	}

	/**
	 * Counts new and existing episodes without importing anything.
	 *
	 * @return array|\WP_Error
	 */
	public static function preview() {
		$url = self::feed_url();
		if ( '' === $url ) {
			return new \WP_Error( 'podcast_core_no_feed', __( 'Set the feed address in Settings → Podcast first.', 'podcast-core-plugin' ) );
		}
		$episodes = Feed::fetch( $url );
		if ( is_wp_error( $episodes ) ) {
			return $episodes;
		}
		$new = array_values( array_filter( $episodes, fn( $e ) => ! self::find( $e ) ) );
		return array(
			'total'    => count( $episodes ),
			'new'      => count( $new ),
			'existing' => count( $episodes ) - count( $new ),
			'latest'   => $episodes ? end( $episodes )['title'] : '',
			'sample'   => array_slice( array_map( fn( $e ) => $e['title'], array_reverse( $new ) ), 0, 5 ),
		);
	}

	/**
	 * Existing episode for a feed item: same guid, or (for episodes added by hand) same audio URL.
	 */
	public static function find( array $episode ): int {
		$by_guid = get_posts(
			array(
				'post_type'      => PODCAST_CORE_EPISODE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => 'podcast_core_guid', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $episode['guid'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		if ( $by_guid ) {
			return (int) $by_guid[0];
		}
		if ( $episode['audio_url'] ) {
			$by_audio = get_posts(
				array(
					'post_type'      => PODCAST_CORE_EPISODE,
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_key'       => 'podcast_core_audio_url', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => $episode['audio_url'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				)
			);
			if ( $by_audio ) {
				update_post_meta( $by_audio[0], 'podcast_core_guid', $episode['guid'] );
				return (int) $by_audio[0];
			}
		}
		return 0;
	}

	/**
	 * @return int|\WP_Error New post ID.
	 */
	public static function create( array $episode ) {
		$id = wp_insert_post(
			array(
				'post_type'     => PODCAST_CORE_EPISODE,
				'post_status'   => 'publish',
				'post_title'    => wp_strip_all_tags( $episode['title'] ),
				'post_content'  => self::to_blocks( $episode['html'] ),
				'post_date_gmt' => $episode['date_gmt'],
				'post_date'     => get_date_from_gmt( $episode['date_gmt'] ),
				'post_author'   => self::author(),
				'meta_input'    => array_filter(
					array(
						'podcast_core_guid'           => $episode['guid'],
						'podcast_core_audio_url'      => esc_url_raw( $episode['audio_url'] ),
						'podcast_core_duration'       => Episode_Meta::sanitize_duration( $episode['duration'] ),
						'podcast_core_episode_number' => $episode['episode_number'],
						'podcast_core_season_number'  => $episode['season_number'],
						'podcast_core_source_url'     => esc_url_raw( $episode['source_url'] ),
					)
				),
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		if ( $episode['image_url'] ) {
			$image = self::image( $episode['image_url'], $id, $episode['title'] );
			if ( ! is_wp_error( $image ) ) {
				set_post_thumbnail( $id, $image );
			}
		}
		/**
		 * Fires after an episode has been imported from the feed.
		 *
		 * @param int   $id      Post ID.
		 * @param array $episode Feed data.
		 */
		do_action( 'podcast_core_episode_imported', $id, $episode );
		return $id;
	}

	/**
	 * Feed HTML → editable blocks: paragraphs and lists become core blocks,
	 * anything else is kept as a Classic block.
	 */
	public static function to_blocks( string $html ): string {
		$html = wp_kses_post( trim( $html ) );
		if ( '' === $html ) {
			return '';
		}
		if ( ! class_exists( 'DOMDocument' ) ) {
			return $html;
		}
		$doc      = new \DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$doc->loadHTML( '<?xml encoding="utf-8"?><div id="pc-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_use_internal_errors( $previous );
		$root = $doc->getElementById( 'pc-root' );
		if ( ! $root ) {
			return $html;
		}

		$blocks = array();
		$inline = '';
		$flush  = function () use ( &$inline, &$blocks ) {
			$text = trim( $inline );
			if ( '' !== $text && '<br>' !== $text ) {
				$blocks[] = "<!-- wp:paragraph -->\n<p>" . $text . "</p>\n<!-- /wp:paragraph -->";
			}
			$inline = '';
		};
		$inner = function ( \DOMNode $node ) use ( $doc ): string {
			$out = '';
			foreach ( $node->childNodes as $child ) {
				$out .= $doc->saveHTML( $child );
			}
			return trim( $out );
		};

		foreach ( iterator_to_array( $root->childNodes ) as $node ) {
			$tag = $node instanceof \DOMElement ? strtolower( $node->tagName ) : '';
			if ( 'p' === $tag ) {
				$flush();
				$text = $inner( $node );
				if ( '' !== $text && '<br>' !== $text ) {
					$blocks[] = "<!-- wp:paragraph -->\n<p>" . $text . "</p>\n<!-- /wp:paragraph -->";
				}
			} elseif ( 'ul' === $tag || 'ol' === $tag ) {
				$flush();
				$items = '';
				foreach ( $node->childNodes as $li ) {
					if ( $li instanceof \DOMElement && 'li' === strtolower( $li->tagName ) ) {
						$items .= "<!-- wp:list-item -->\n<li>" . $inner( $li ) . "</li>\n<!-- /wp:list-item -->\n";
					}
				}
				$attrs    = 'ol' === $tag ? ' {"ordered":true}' : '';
				$blocks[] = "<!-- wp:list{$attrs} -->\n<{$tag} class=\"wp-block-list\">" . $items . "</{$tag}>\n<!-- /wp:list -->";
			} elseif ( in_array( $tag, array( 'h2', 'h3', 'h4' ), true ) ) {
				$flush();
				$level    = (int) substr( $tag, 1 );
				$attrs    = 2 === $level ? '' : ' {"level":' . $level . '}';
				$blocks[] = "<!-- wp:heading{$attrs} -->\n<{$tag} class=\"wp-block-heading\">" . $inner( $node ) . "</{$tag}>\n<!-- /wp:heading -->";
			} elseif ( in_array( $tag, array( '', 'strong', 'em', 'b', 'i', 'a', 'span', 'br' ), true ) ) {
				$inline .= $doc->saveHTML( $node );
			} else {
				$flush();
				$blocks[] = "<!-- wp:freeform -->\n" . $doc->saveHTML( $node ) . "\n<!-- /wp:freeform -->";
			}
		}
		$flush();
		return implode( "\n\n", $blocks );
	}

	/**
	 * Downloads an image into the Media Library once; later episodes with the same
	 * image reuse the attachment.
	 *
	 * @return int|\WP_Error Attachment ID.
	 */
	public static function image( string $url, int $post_id, string $title ) {
		$existing = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_podcast_core_source_url', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $url, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		if ( $existing ) {
			return (int) $existing[0];
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = download_url( $url, 60 );
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}
		$mime = wp_get_image_mime( $tmp );
		$exts = array(
			'image/jpeg' => 'jpg',
			'image/png'  => 'png',
			'image/webp' => 'webp',
			'image/gif'  => 'gif',
		);
		if ( ! isset( $exts[ $mime ] ) ) {
			wp_delete_file( $tmp );
			return new \WP_Error( 'podcast_core_image_type', __( 'The episode image is not a supported image type.', 'podcast-core-plugin' ) );
		}
		$file = array(
			'name'     => sanitize_title( mb_substr( $title, 0, 60 ) ) . '.' . $exts[ $mime ],
			'tmp_name' => $tmp,
		);
		$id   = media_handle_sideload( $file, $post_id, $title );
		if ( is_wp_error( $id ) ) {
			wp_delete_file( $tmp );
			return $id;
		}
		update_post_meta( $id, '_podcast_core_source_url', $url );
		update_post_meta( $id, '_wp_attachment_image_alt', $title );
		return $id;
	}

	/**
	 * Author of imported episodes: the current user, or the first administrator for cron.
	 */
	private static function author(): int {
		$user = get_current_user_id();
		if ( $user ) {
			return $user;
		}
		$admins = get_users(
			array(
				'role'    => 'administrator',
				'number'  => 1,
				'orderby' => 'ID',
				'fields'  => 'ID',
			)
		);
		return $admins ? (int) $admins[0] : 0;
	}

	private static function save_status( string $trigger, array $result ): void {
		update_option(
			self::STATUS_OPT,
			array(
				'time'    => time(),
				'trigger' => $trigger,
				'result'  => $result,
			),
			false
		);
	}
}
