<?php
/**
 * Card grid. Cards are a repeater, not InnerBlocks.
 *
 * @var array<string, mixed> $a
 * @package Spa_Headless
 */

list( $section_classes, $container_classes ) = spa_section_classes( $a, 'spa-card-grid-section' );
$cards = isset( $a['cards'] ) && is_array( $a['cards'] ) ? $a['cards'] : array();
?>
<section useBlockProps class="<?php echo esc_attr( $section_classes ); ?>">
	<div class="<?php echo esc_attr( $container_classes ); ?>">
		<?php if ( ! empty( $a['heading'] ) && is_string( $a['heading'] ) ) : ?>
			<h2><?php echo esc_html( $a['heading'] ); ?></h2>
		<?php endif; ?>

		<?php if ( ! empty( $a['intro'] ) && is_string( $a['intro'] ) ) : ?>
			<p class="spa-lead"><?php echo esc_html( $a['intro'] ); ?></p>
		<?php endif; ?>

		<?php if ( array() !== $cards ) : ?>
			<div class="spa-card-grid">
				<?php foreach ( $cards as $card ) : ?>
					<?php
					if ( ! is_array( $card ) ) {
						continue;
					}
					$title = isset( $card['title'] ) && is_string( $card['title'] ) ? $card['title'] : '';
					$text  = isset( $card['text'] ) && is_string( $card['text'] ) ? $card['text'] : '';
					$link  = spa_link_values( $card['link'] ?? null );
					?>
					<article class="spa-card">
						<?php if ( '' !== $title ) : ?>
							<h3><?php echo esc_html( $title ); ?></h3>
						<?php endif; ?>
						<?php if ( '' !== $text ) : ?>
							<p><?php echo esc_html( $text ); ?></p>
						<?php endif; ?>
						<?php if ( '' !== $link['url'] ) : ?>
							<a class="spa-button" href="<?php echo esc_url( $link['url'] ); ?>"<?php echo $link['new_tab'] ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
								<?php echo esc_html( '' !== $link['title'] ? $link['title'] : $title ); ?>
							</a>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
