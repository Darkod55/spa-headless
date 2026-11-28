<?php
/**
 * Shared markup for Hero Banner Main and Hero Banner Default.
 *
 * The Astro component repeats this class structure. Styles live in
 * assets/hero-banner.css, which the editor and the Astro app both load.
 *
 * @var array<string, mixed> $a
 * @var string               $spa_hero_variant main|default
 * @package Spa_Headless
 */

$variant  = isset( $spa_hero_variant ) && 'main' === $spa_hero_variant ? 'main' : 'default';
$modifier = function_exists( 'spa_hero_banner_modifier' )
	? spa_hero_banner_modifier( $a['background'] ?? null )
	: 'hero--bg-navy';
$variant_class = function_exists( 'spa_hero_banner_variant_class' )
	? spa_hero_banner_variant_class( $variant )
	: ( 'main' === $variant ? 'hero--main' : 'hero--default' );

$image = function_exists( 'spa_headless_prepare_hero_image' )
	? spa_headless_prepare_hero_image( $a['image'] ?? null )
	: null;

$classes = 'hero ' . $variant_class . ' ' . $modifier;

$template = array(
	array(
		'core/heading',
		array(
			'level'       => 1,
			'placeholder' => 'Hero heading',
		),
	),
	array(
		'core/paragraph',
		array(
			'placeholder' => 'Supporting line',
		),
	),
);
?>
<div class="hero-root">
	<section useBlockProps class="<?php echo esc_attr( $classes ); ?>">
		<?php if ( is_array( $image ) && '' !== $image['url'] ) : ?>
			<div class="hero__photo">
				<img
					class="hero__img"
					src="<?php echo esc_url( $image['url'] ); ?>"
					alt="<?php echo esc_attr( $image['alt'] ); ?>"
					<?php echo null !== $image['width'] ? 'width="' . esc_attr( (string) $image['width'] ) . '"' : ''; ?>
					<?php echo null !== $image['height'] ? 'height="' . esc_attr( (string) $image['height'] ) . '"' : ''; ?>
					decoding="async"
				>
			</div>
		<?php else : ?>
			<div class="hero__photo hero__photo--placeholder" aria-hidden="true"></div>
		<?php endif; ?>

		<div class="hero__bands" aria-hidden="true">
			<div class="hero__band hero__band--shape-1"></div>
			<div class="hero__band hero__band--shape-2"></div>
			<?php if ( 'main' === $variant ) : ?>
				<div class="hero__band hero__band--bg-corner"></div>
			<?php endif; ?>
			<div class="hero__band hero__band--corner"></div>
			<div class="hero__band hero__band--mobile-edge"></div>
			<?php if ( 'main' === $variant ) : ?>
				<div class="hero-squares">
					<div class="hero-squares__sq hero-squares__sq--1"></div>
					<div class="hero-squares__sq hero-squares__sq--2"></div>
				</div>
			<?php endif; ?>
		</div>

		<div class="hero__content">
			<div class="container hero__inner">
				<div class="hero__copy">
					<InnerBlocks
						allowedBlocks="<?php echo spa_json_attr( array( 'core/heading', 'core/paragraph' ) ); ?>"
						template="<?php echo spa_json_attr( $template ); ?>"
					/>
				</div>
			</div>
		</div>
	</section>
</div>
