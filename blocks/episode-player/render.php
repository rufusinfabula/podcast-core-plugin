<?php
/**
 * Episode Player block — server-side render.
 *
 * @package PodcastCore
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$podcast_core_post_id = $block->context['postId'] ?? get_the_ID();
if ( ! $podcast_core_post_id && isset( $_GET['post_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only preview in the editor.
	$podcast_core_post_id = absint( $_GET['post_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}
if ( ! $podcast_core_post_id || PODCAST_CORE_EPISODE !== get_post_type( $podcast_core_post_id ) ) {
	return;
}

$podcast_core_audio = podcast_core_get_field( 'audio_url', $podcast_core_post_id );
$podcast_core_video = podcast_core_get_field( 'video_url', $podcast_core_post_id );
$podcast_core_num   = podcast_core_get_field( 'episode_number', $podcast_core_post_id );
$podcast_core_title = get_the_title( $podcast_core_post_id );
$podcast_core_thumb = ! empty( $attributes['showCover'] ) ? get_the_post_thumbnail( $podcast_core_post_id, 'medium_large', array( 'class' => 'pc-player__cover-img', 'alt' => '' ) ) : '';
$podcast_core_secs  = podcast_core_duration_to_seconds( (string) podcast_core_get_field( 'duration', $podcast_core_post_id ) );

if ( ! $podcast_core_audio && ! ( $podcast_core_video && ! empty( $attributes['showVideo'] ) ) ) {
	return;
}
if ( ! $podcast_core_audio ) {
	// Video only: no player bar.
	$podcast_core_embed = wp_oembed_get( $podcast_core_video );
	echo $podcast_core_embed ? '<div class="pc-player-video">' . $podcast_core_embed . '</div>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'pc-player' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $podcast_core_audio ) : ?>
	<div class="pc-player__main">
		<?php if ( $podcast_core_thumb ) : ?>
			<div class="pc-player__cover"><?php echo $podcast_core_thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php endif; ?>
		<div class="pc-player__body">
			<?php if ( ! empty( $attributes['showNumber'] ) && $podcast_core_num ) : ?>
				<p class="pc-player__number">
					<?php
					/* translators: %s: episode number */
					printf( esc_html__( 'Episode %s', 'podcast-core-engine' ), '<span class="pc-player__number-value">' . esc_html( $podcast_core_num ) . '</span>' );
					?>
				</p>
			<?php endif; ?>
			<p class="pc-player__title"><?php echo esc_html( $podcast_core_title ); ?></p>

			<audio class="pc-player__audio" src="<?php echo esc_url( $podcast_core_audio ); ?>" preload="metadata" controls></audio>

			<div class="pc-player__controls" hidden>
				<button type="button" class="pc-player__toggle" aria-label="<?php esc_attr_e( 'Play', 'podcast-core-engine' ); ?>" data-label-play="<?php esc_attr_e( 'Play', 'podcast-core-engine' ); ?>" data-label-pause="<?php esc_attr_e( 'Pause', 'podcast-core-engine' ); ?>">
					<span class="pc-player__icon" aria-hidden="true"></span>
				</button>
				<input type="range" class="pc-player__seek" min="0" max="<?php echo esc_attr( $podcast_core_secs ? $podcast_core_secs : 100 ); ?>" step="1" value="0" aria-label="<?php esc_attr_e( 'Seek', 'podcast-core-engine' ); ?>" />
				<span class="pc-player__time"><span class="pc-player__current">0:00</span> / <span class="pc-player__duration"><?php echo esc_html( $podcast_core_secs ? gmdate( $podcast_core_secs >= 3600 ? 'G:i:s' : 'i:s', $podcast_core_secs ) : '–' ); ?></span></span>
			</div>
		</div>
	</div>
	<?php endif; ?>
</div>
<?php
// Video shown elsewhere (Episode Video block): a link under the player that scrolls to it.
// Styles: pill (outlined button), card (episode image with a play sign, title and hint), bar
// (a band attached under the player, as wide as it).
if ( $podcast_core_video && empty( $attributes['showVideo'] ) && ! empty( $attributes['showVideoLink'] ) ) {
	$podcast_core_style = in_array( $attributes['videoLinkStyle'] ?? 'pill', array( 'pill', 'card', 'bar' ), true ) ? $attributes['videoLinkStyle'] : 'pill';
	$podcast_core_align = ! empty( $attributes['align'] ) ? ' align' . sanitize_html_class( $attributes['align'] ) : '';
	$podcast_core_cam   = '<svg class="pc-player-videolink__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="2" y="5" width="15" height="14" rx="2"></rect><path d="m17 10 5-3v10l-5-3z"></path></svg>';
	$podcast_core_arrow = '<svg class="pc-player-videolink__arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 5v14"></path><path d="m19 12-7 7-7-7"></path></svg>';
	$podcast_core_label = esc_html__( 'Watch the video', 'podcast-core-engine' );
	if ( 'card' === $podcast_core_style ) {
		$podcast_core_img = get_the_post_thumbnail( $podcast_core_post_id, 'medium', array( 'alt' => '', 'loading' => 'lazy' ) );
		$podcast_core_inner = '<span class="pc-player-videolink__media">' . $podcast_core_img . '<span class="pc-player-videolink__play" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" focusable="false"><path d="M8 5v14l11-7z"></path></svg></span></span>'
			. '<span class="pc-player-videolink__text"><strong>' . $podcast_core_label . '</strong><span>' . esc_html__( 'This episode is also available as a video podcast', 'podcast-core-engine' ) . '</span></span>'
			. $podcast_core_arrow;
	} else {
		$podcast_core_inner = $podcast_core_cam . '<span>' . $podcast_core_label . '</span>' . $podcast_core_arrow;
	}
	$podcast_core_in_text = 'content' === ( $attributes['videoLinkPosition'] ?? 'player' );
	$podcast_core_link    = sprintf(
		'<div class="pc-player-videolink pc-player-videolink--%1$s%2$s"><a href="#pc-episode-video">%3$s</a></div>',
		esc_attr( $podcast_core_style ),
		esc_attr( $podcast_core_in_text ? ' pc-player-videolink--in-text' : $podcast_core_align ),
		$podcast_core_inner
	);
	if ( $podcast_core_in_text ) {
		// Placed in the episode text, after its Nth block (see Blocks::video_link_in_text()).
		PodcastCore\Blocks::$video_link       = $podcast_core_link;
		PodcastCore\Blocks::$video_link_after = max( 1, (int) ( $attributes['videoLinkAfter'] ?? 1 ) );
	} else {
		echo $podcast_core_link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above.
	}
}

// The video is a sibling of the player, not a child: themes can make the player
// sticky without the (tall) video sticking with it.
if ( $podcast_core_video && ! empty( $attributes['showVideo'] ) ) {
	$podcast_core_embed = wp_oembed_get( $podcast_core_video );
	if ( $podcast_core_embed ) {
		$podcast_core_align = ! empty( $attributes['align'] ) ? ' align' . sanitize_html_class( $attributes['align'] ) : '';
		echo '<div class="pc-player-video' . esc_attr( $podcast_core_align ) . '">' . $podcast_core_embed . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- oEmbed HTML from a trusted provider.
	}
}
