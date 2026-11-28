<?php
/**
 * Shared markup for spa/hero-banner-main and spa/hero-banner-default.
 *
 * The Astro component repeats this class structure. Styles live in
 * resources/css/components/spa-hero-banner.css.
 *
 * @var array<string, mixed> $a
 * @var string               $spa_hero_variant main|default
 */

if (! function_exists('spa_sage_hero_modifier')) {
    /**
     * @param mixed $background Colour object, slug, or CSS var.
     */
    function spa_sage_hero_modifier($background): string
    {
        $slug = 'navy';
        if (is_array($background) && isset($background['slug']) && is_string($background['slug'])) {
            $slug = $background['slug'];
        } elseif (is_string($background) && preg_match('/^var\(--color-([a-z0-9-]+)\)$/', $background, $match)) {
            $slug = $match[1];
        } elseif (is_string($background) && $background !== '') {
            $slug = $background;
        }

        $aliases = [
            'primary' => 'navy',
            'secondary' => 'red',
            'tertiary' => 'royal',
            'grey' => 'gray',
            'red-deep' => 'secondary-deep',
        ];
        if (isset($aliases[$slug])) {
            $slug = $aliases[$slug];
        }

        $known = ['navy', 'red', 'royal', 'black', 'white', 'gray', 'secondary-deep'];

        return 'hero--bg-' . (in_array($slug, $known, true) ? $slug : 'navy');
    }
}

if (! function_exists('spa_sage_json_attr')) {
    function spa_sage_json_attr($value): string
    {
        return htmlspecialchars(
            (string) wp_json_encode($value),
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

$variant = isset($spa_hero_variant) && 'main' === $spa_hero_variant ? 'main' : 'default';
$modifier = spa_sage_hero_modifier($a['background'] ?? null);
$variant_class = 'main' === $variant ? 'hero--main' : 'hero--default';

$image = function_exists('spa_headless_prepare_hero_image')
    ? spa_headless_prepare_hero_image($a['image'] ?? null)
    : null;

$classes = 'hero ' . $variant_class . ' ' . $modifier;

$template = [
    ['spa/badge', []],
    [
        'core/heading',
        [
            'level' => 1,
            'placeholder' => 'Hero heading',
        ],
    ],
    [
        'core/paragraph',
        [
            'placeholder' => 'Supporting line',
        ],
    ],
];
?>
<div class="hero-root">
	<section useBlockProps class="<?php echo esc_attr($classes); ?>">
		<?php if (is_array($image) && '' !== $image['url']) : ?>
			<div class="hero__photo">
				<img
					class="hero__img"
					src="<?php echo esc_url($image['url']); ?>"
					alt="<?php echo esc_attr($image['alt']); ?>"
					<?php echo null !== $image['width'] ? 'width="' . esc_attr((string) $image['width']) . '"' : ''; ?>
					<?php echo null !== $image['height'] ? 'height="' . esc_attr((string) $image['height']) . '"' : ''; ?>
					decoding="async"
				>
			</div>
		<?php else : ?>
			<div class="hero__photo hero__photo--placeholder" aria-hidden="true"></div>
		<?php endif; ?>

		<div class="hero__bands" aria-hidden="true">
			<div class="hero__band hero__band--shape-1"></div>
			<div class="hero__band hero__band--shape-2"></div>
			<?php if ('main' === $variant) : ?>
				<div class="hero__band hero__band--bg-corner"></div>
			<?php endif; ?>
			<div class="hero__band hero__band--corner"></div>
			<div class="hero__band hero__band--mobile-edge"></div>
			<?php if ('main' === $variant) : ?>
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
						allowedBlocks="<?php echo spa_sage_json_attr(['spa/badge', 'core/heading', 'core/paragraph']); ?>"
						template="<?php echo spa_sage_json_attr($template); ?>"
						templateLock="false"
					/>
				</div>
			</div>
		</div>
	</section>
</div>
