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
					printf( esc_html__( 'Episode %s', 'podcast-core-plugin' ), '<span class="pc-player__number-value">' . esc_html( $podcast_core_num ) . '</span>' );
					?>
				</p>
			<?php endif; ?>
			<p class="pc-player__title"><?php echo esc_html( $podcast_core_title ); ?></p>

			<audio class="pc-player__audio" src="<?php echo esc_url( $podcast_core_audio ); ?>" preload="metadata" controls></audio>

			<div class="pc-player__controls" hidden>
				<button type="button" class="pc-player__toggle" aria-label="<?php esc_attr_e( 'Play', 'podcast-core-plugin' ); ?>" data-label-play="<?php esc_attr_e( 'Play', 'podcast-core-plugin' ); ?>" data-label-pause="<?php esc_attr_e( 'Pause', 'podcast-core-plugin' ); ?>">
					<span class="pc-player__icon" aria-hidden="true"></span>
				</button>
				<input type="range" class="pc-player__seek" min="0" max="<?php echo esc_attr( $podcast_core_secs ? $podcast_core_secs : 100 ); ?>" step="1" value="0" aria-label="<?php esc_attr_e( 'Seek', 'podcast-core-plugin' ); ?>" />
				<span class="pc-player__time"><span class="pc-player__current">0:00</span> / <span class="pc-player__duration"><?php echo esc_html( $podcast_core_secs ? gmdate( $podcast_core_secs >= 3600 ? 'G:i:s' : 'i:s', $podcast_core_secs ) : '–' ); ?></span></span>
			</div>
		</div>
	</div>
	<?php endif; ?>
</div>
<?php
// The video is a sibling of the player, not a child: themes can make the player
// sticky without the (tall) video sticking with it.
if ( $podcast_core_video && ! empty( $attributes['showVideo'] ) ) {
	$podcast_core_embed = wp_oembed_get( $podcast_core_video );
	if ( $podcast_core_embed ) {
		$podcast_core_align = ! empty( $attributes['align'] ) ? ' align' . sanitize_html_class( $attributes['align'] ) : '';
		echo '<div class="pc-player-video' . esc_attr( $podcast_core_align ) . '">' . $podcast_core_embed . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- oEmbed HTML from a trusted provider.
	}
}
