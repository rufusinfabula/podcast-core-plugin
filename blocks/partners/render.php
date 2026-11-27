<?php
/**
 * Partners block — server-side render.
 *
 * @package PodcastCore
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$podcast_core_partners = PodcastCore\Partners::get( (string) ( $attributes['type'] ?? '' ), (int) ( $attributes['limit'] ?? 0 ) );
if ( ! $podcast_core_partners ) {
	return;
}

$podcast_core_columns = max( 1, min( 6, (int) ( $attributes['columns'] ?? 4 ) ) );
$podcast_core_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'pc-partners pc-partners--columns-' . $podcast_core_columns,
		'style' => '--pc-partners-columns:' . $podcast_core_columns . ';',
	)
);
?>
<div <?php echo $podcast_core_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<ul class="pc-partners__list">
		<?php
		foreach ( $podcast_core_partners as $podcast_core_partner ) :
			$podcast_core_url  = (string) get_post_meta( $podcast_core_partner->ID, PodcastCore\Partners::URL_META, true );
			$podcast_core_name = get_the_title( $podcast_core_partner );
			$podcast_core_tag  = $podcast_core_url ? 'a' : 'div';
			$podcast_core_attr = $podcast_core_url ? sprintf( ' href="%s" target="_blank" rel="noopener"', esc_url( $podcast_core_url ) ) : '';
			?>
			<li class="pc-partner">
				<<?php echo $podcast_core_tag . $podcast_core_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed tag, URL escaped above. ?> class="pc-partner__inner">
					<?php if ( has_post_thumbnail( $podcast_core_partner ) ) : ?>
						<span class="pc-partner__logo">
							<?php
							echo get_the_post_thumbnail(
								$podcast_core_partner,
								'medium',
								array(
									'alt'     => $attributes['showName'] ? '' : $podcast_core_name,
									'loading' => 'lazy',
								)
							);
							?>
						</span>
					<?php endif; ?>
					<?php if ( ! empty( $attributes['showName'] ) || ! has_post_thumbnail( $podcast_core_partner ) ) : ?>
						<span class="pc-partner__name"><?php echo esc_html( $podcast_core_name ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $attributes['showText'] ) && has_excerpt( $podcast_core_partner ) ) : ?>
						<span class="pc-partner__text"><?php echo esc_html( get_the_excerpt( $podcast_core_partner ) ); ?></span>
					<?php endif; ?>
				</<?php echo $podcast_core_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
