<?php
/**
 * Series list block — server-side render.
 *
 * Lists the top-level series, or the sub-series of the series chosen in "parent" (slug).
 * The count is the number of published episodes assigned to each series. With "episodes" > 0,
 * the images of the latest episodes of each series are shown under it, newest on the left, each
 * overlapping the next one by 30% and together filling the width of the card; when the series has
 * more episodes, a cut card with an ellipsis follows. The whole card
 * (name, count and images) links to the series page. With orderBy
 * "latest" the series with the most recent episode comes first.
 *
 * @package PodcastCore
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$podcast_core_parent = 0;
if ( ! empty( $attributes['parent'] ) ) {
	$podcast_core_term = get_term_by( 'slug', (string) $attributes['parent'], PODCAST_CORE_SERIES );
	if ( ! $podcast_core_term ) {
		return;
	}
	$podcast_core_parent = (int) $podcast_core_term->term_id;
}

$podcast_core_series = get_terms(
	array(
		'taxonomy'   => PODCAST_CORE_SERIES,
		'parent'     => $podcast_core_parent,
		'hide_empty' => ! empty( $attributes['hideEmpty'] ),
		'orderby'    => 'name',
	)
);
if ( is_wp_error( $podcast_core_series ) || ! $podcast_core_series ) {
	return;
}

$podcast_core_episodes = max( 0, min( 12, (int) ( $attributes['episodes'] ?? 0 ) ) );
$podcast_core_by_last  = 'latest' === ( $attributes['orderBy'] ?? 'name' );

// Latest episodes of each series (at least one when ordering by the latest episode).
$podcast_core_latest_by_term = array();
foreach ( $podcast_core_series as $podcast_core_item ) {
	$podcast_core_how_many = $podcast_core_by_last ? max( 1, $podcast_core_episodes ) : $podcast_core_episodes;
	$podcast_core_latest_by_term[ $podcast_core_item->term_id ] = $podcast_core_how_many ? get_posts(
		array(
			'post_type'      => PODCAST_CORE_EPISODE,
			'post_status'    => 'publish',
			'posts_per_page' => $podcast_core_how_many,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy'         => PODCAST_CORE_SERIES,
					'terms'            => $podcast_core_item->term_id,
					'include_children' => false,
				),
			),
		)
	) : array();
}

// Series with the most recent episode first; series without episodes last, by name.
if ( $podcast_core_by_last ) {
	usort(
		$podcast_core_series,
		static function ( $a, $b ) use ( $podcast_core_latest_by_term ) {
			$da = $podcast_core_latest_by_term[ $a->term_id ] ? $podcast_core_latest_by_term[ $a->term_id ][0]->post_date_gmt : '';
			$db = $podcast_core_latest_by_term[ $b->term_id ] ? $podcast_core_latest_by_term[ $b->term_id ][0]->post_date_gmt : '';
			return $db <=> $da ?: strcasecmp( $a->name, $b->name );
		}
	);
}
$podcast_core_columns  = max( 1, min( 6, (int) ( $attributes['columns'] ?? 3 ) ) );
$podcast_core_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'pc-series-list pc-series-list--columns-' . $podcast_core_columns,
		'style' => '--pc-series-columns:' . $podcast_core_columns . ';',
	)
);
?>
<div <?php echo $podcast_core_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<ul class="pc-series-list__list">
		<?php
		foreach ( $podcast_core_series as $podcast_core_item ) :
			$podcast_core_icon = ! empty( $attributes['showIcon'] ) ? PodcastCore\Series_Icons::get( $podcast_core_item->term_id ) : 0;
			$podcast_core_link = get_term_link( $podcast_core_item );
			$podcast_core_latest = $podcast_core_latest_by_term[ $podcast_core_item->term_id ];
			?>
			<li class="pc-series<?php echo $podcast_core_icon ? ' pc-series--has-icon' : ''; ?>">
				<a class="pc-series__inner" href="<?php echo esc_url( is_wp_error( $podcast_core_link ) ? '' : $podcast_core_link ); ?>">
					<span class="pc-series__head">
						<?php if ( $podcast_core_icon ) : ?>
							<span class="pc-series__icon"><?php echo wp_get_attachment_image( $podcast_core_icon, 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy' ) ); ?></span>
						<?php endif; ?>
						<span class="pc-series__body">
							<span class="pc-series__name"><?php echo esc_html( $podcast_core_item->name ); ?></span>
							<?php if ( ! empty( $attributes['showDescription'] ) && $podcast_core_item->description ) : ?>
								<span class="pc-series__text"><?php echo esc_html( wp_trim_words( $podcast_core_item->description, 24 ) ); ?></span>
							<?php endif; ?>
						</span>
						<?php if ( ! empty( $attributes['showCount'] ) ) : ?>
							<span class="pc-series__count">
								<?php
								/* translators: %s: number of episodes */
								echo esc_html( sprintf( _n( '%s episode', '%s episodes', $podcast_core_item->count, 'podcast-core-engine' ), number_format_i18n( $podcast_core_item->count ) ) );
								?>
							</span>
						<?php endif; ?>
					</span>
					<?php
					// The images are part of the card: the whole card links to the series page.
					$podcast_core_latest = array_slice( $podcast_core_latest, 0, $podcast_core_episodes );
					$podcast_core_latest = array_values( array_filter( $podcast_core_latest, 'has_post_thumbnail' ) );
					if ( $podcast_core_latest ) :
						$podcast_core_shown = count( $podcast_core_latest );
						$podcast_core_more  = max( 0, (int) $podcast_core_item->count - $podcast_core_shown );
						?>
						<span class="pc-series__episodes" style="--pc-series-episodes:<?php echo (int) $podcast_core_shown; ?>;--pc-series-more:<?php echo $podcast_core_more ? 1 : 0; ?>;">
							<?php foreach ( $podcast_core_latest as $podcast_core_i => $podcast_core_episode ) : ?>
								<span class="pc-series__episode" style="z-index:<?php echo (int) ( $podcast_core_shown - $podcast_core_i ); ?>;">
									<?php echo get_the_post_thumbnail( $podcast_core_episode, 'medium', array( 'alt' => '', 'loading' => 'lazy' ) ); ?>
								</span>
							<?php endforeach; ?>
							<?php if ( $podcast_core_more ) : ?>
								<span class="pc-series__more" style="z-index:0;">
									<span class="pc-series__dots" aria-hidden="true"><span></span><span></span><span></span></span>
									<span class="screen-reader-text">
										<?php
										/* translators: %s: number of further episodes */
										echo esc_html( sprintf( _n( 'and %s more episode', 'and %s more episodes', $podcast_core_more, 'podcast-core-engine' ), number_format_i18n( $podcast_core_more ) ) );
										?>
									</span>
								</span>
							<?php endif; ?>
						</span>
					<?php endif; ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
