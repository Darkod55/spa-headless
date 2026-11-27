<?php
/**
 * Hero block. Rendered in the editor and on the WordPress preview.
 * The public markup lives in astro/src/components/blocks/spa/Hero.astro.
 *
 * @var array<string, mixed> $a
 * @package Spa_Headless
 */

list( $section_classes, $container_classes ) = spa_section_classes( $a, 'spa-hero' );
$primary   = spa_link_values( $a['primaryButton'] ?? null );
$secondary = spa_link_values( $a['secondaryButton'] ?? null );
$image     = spa_file_values( $a['image'] ?? null );
?>
<section useBlockProps class="<?php echo esc_attr( $section_classes ); ?>">
	<div class="<?php echo esc_attr( $container_classes ); ?>">
		<?php if ( ! empty( $a['eyebrow'] ) && is_string( $a['eyebrow'] ) ) : ?>
			<p class="spa-kicker"><?php echo esc_html( $a['eyebrow'] ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $a['heading'] ) && is_string( $a['heading'] ) ) : ?>
			<h1><?php echo esc_html( $a['heading'] ); ?></h1>
		<?php endif; ?>

		<?php if ( ! empty( $a['description'] ) && is_string( $a['description'] ) ) : ?>
			<p class="spa-lead"><?php echo esc_html( $a['description'] ); ?></p>
		<?php endif; ?>

		<?php if ( '' !== $primary['url'] || '' !== $secondary['url'] ) : ?>
			<div class="spa-actions">
				<?php if ( '' !== $primary['url'] ) : ?>
					<a class="spa-button is-primary" href="<?php echo esc_url( $primary['url'] ); ?>"<?php echo $primary['new_tab'] ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
						<?php echo esc_html( '' !== $primary['title'] ? $primary['title'] : $primary['url'] ); ?>
					</a>
				<?php endif; ?>
				<?php if ( '' !== $secondary['url'] ) : ?>
					<a class="spa-button" href="<?php echo esc_url( $secondary['url'] ); ?>"<?php echo $secondary['new_tab'] ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
						<?php echo esc_html( '' !== $secondary['title'] ? $secondary['title'] : $secondary['url'] ); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $image['url'] ) : ?>
			<div class="spa-hero-media">
				<img src="<?php echo esc_url( $image['url'] ); ?>" alt="<?php echo esc_attr( $image['alt'] ); ?>">
			</div>
		<?php endif; ?>
	</div>
</section>
