<?php
/**
 * spa/badge. The Astro component repeats these classes.
 * Styles: resources/css/components/spa-badge.css (the live Label, default size).
 * The letter stagger is Astro-only. This template stays one RichText.
 *
 * @var array<string, mixed> $a
 */

if (! function_exists('spa_badge_slug')) {
    /**
     * @param mixed $value Colour object, slug, or CSS variable.
     */
    function spa_badge_slug($value, string $fallback): string
    {
        if (is_array($value) && isset($value['slug']) && is_string($value['slug']) && $value['slug'] !== '') {
            return $value['slug'];
        }
        if (is_string($value) && preg_match('/^var\(--color-([a-z0-9-]+)\)$/', $value, $match)) {
            return $match[1];
        }
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return $fallback;
    }
}

if (! function_exists('spa_badge_text_slug')) {
    /**
     * White type on a white or gray badge is unreadable, so that pair uses navy.
     *
     * @param mixed $background
     * @param mixed $text
     */
    function spa_badge_text_slug($background, $text): string
    {
        $bg = spa_badge_slug($background, 'secondary');
        $chosen = spa_badge_slug($text, 'white');
        $light = ['white', 'gray', 'grey'];
        if ('white' === $chosen && in_array($bg, $light, true)) {
            return 'primary';
        }

        return $chosen;
    }
}

$bg = spa_badge_slug($a['bg_colour'] ?? null, 'secondary');
$text = spa_badge_text_slug($a['bg_colour'] ?? null, $a['text_colour'] ?? null);
$classes = 'badge badge--bg-' . $bg . ' badge--text-' . $text;
?>
<span useBlockProps class="<?php echo esc_attr($classes); ?>">
	<RichText
		attribute="text"
		tag="span"
		class="badge__text"
		placeholder="Different by design"
		allowedFormats="[]"
	/>
</span>
