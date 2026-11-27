<?php
/**
 * Episode Video block — server-side render: the episode's video embed, nothing when the
 * episode has no video.
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
$podcast_core_video = (string) podcast_core_get_field( 'video_url', $podcast_core_post_id );
$podcast_core_embed = $podcast_core_video ? wp_oembed_get( $podcast_core_video ) : false;
if ( ! $podcast_core_embed ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'pc-episode-video', 'id' => 'pc-episode-video' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo $podcast_core_embed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- oEmbed HTML from a trusted provider. ?>
</div>
