<?php

namespace App\Helpers\Traits;

/**
 * Trait icons
 *
 * SVG icons related functions.
 *
 * @package App\Helpers\Traits
 */
trait icons
{
    /**
     * Echo SVG Icon (getSvg helper)
     *
     * @param string $icon      The SVG icon's ID
     * @param int    $width_att Optional width
     * @param string $title     Optional SVG title.
     *
     * @return string           The SVG markup
     */
    public function getSvgIcon(string $icon, int $width_att = 30, string $title = '') : string {
        return empty($title) ? $this->getSvg(['icon' => $icon, 'width' => $width_att]) : $this->getSvg(['icon'=>$icon, 'width' => $width_att, 'title'=>$title]);
    }

    /**
     * Return SVG markup.
     *
     * @param array $args {
     *     Parameters needed to display an SVG.
     *
     *     @type string $icon  Required SVG icon filename.
     *     @type string $title Optional SVG title.
     *     @type string $desc  Optional SVG description.
     * }
     * @return string SVG markup.
     */
    public function getSvg($args = []) {
        // Make sure $args are an array.
        if (empty($args)) {
            _doing_it_wrong(__METHOD__, 'Please define default parameters in the form of an array.', '1.0.0');

            return '';
        }

        // Define an icon.
        if (false === array_key_exists('icon', $args)) {
            _doing_it_wrong(__METHOD__, 'Please define an SVG icon.', '1.0.0');

            return '';
        }

        // Set defaults.
        $defaults = [
            'icon'        => '',
            'title'       => ucfirst($args['icon']),
            'desc'        => ucfirst('icon for '.$args['icon']),
            'fallback'    => false,
            'filter'      => false,
            'style'       => false,
            'focusable'   => false,
            'width'       => 24,
            'height'      => '',
        ];

        // Parse args.
        $args = wp_parse_args($args, $defaults);

        // Set aria hidden.
        $aria_hidden = ' aria-hidden="true"';

        // Set ARIA.
        $aria_labelledby = '';

        // Set focusable.
        /**
         * It is claimed that IE & Edge the SVG can get keyboard, resulting in the surrounding link being focused and read twice.
         *
         * @see: https://fvsch.com/svg-icons/#section-html-e
         */
        $focusable = ' focusable="false"';

        // Set filter
        $filter = $args['filter'] ? " filter=\"url({$args['filter']})\"" : '';

        // Set CSS styles
        $style = $args['style'] ? " style=\"{$args['style']}\"" : '';

        // Set size attributes
        $width_attr = !empty($args['width']) ? " width=\"{$args['width']}\"" : '';

        // Set height from height, or width, or leave empty.
        $height = !empty($args['width']) ? ($args['height'] ?: $args['width']) : '';
        $height_attr = !empty($height) ? " height=\"{$height}\"" : '';

        // The icon
        $icon = esc_attr($args['icon']);

        /**
         * Twenty Seventeen doesn't use the SVG title or description attributes; non-decorative icons are described with .screen-reader-text.
         *
         * However, child themes can use the title and description to add information to non-decorative SVG icons to improve accessibility.
         *
         * Example 1 with title: <?php echo getSvg( array( 'icon' => 'arrow-right', 'title' => __( 'This is the title', 'textdomain' ) ) ); ?>
         *
         * Example 2 with title and description: <?php echo getSvg( array( 'icon' => 'arrow-right', 'title' => __( 'This is the title', 'textdomain' ), 'desc' => __( 'This is the description', 'textdomain' ) ) ); ?>
         *
         * See https://www.paciellogroup.com/blog/2013/12/using-aria-enhance-svg-accessibility/.
         */
        if ($args['title']) {
            $aria_hidden     = '';
            $unique_id       = uniqid();
            $aria_labelledby = ' aria-labelledby="title-'.$unique_id.'"';

            if ($args['desc']) $aria_labelledby = ' aria-labelledby="title-'.$unique_id.' desc-'.$unique_id.'"';
        }

        // Remove the 'focusable' attribute
        if ($args['focusable']) $focusable = '';

        // Begin SVG markup.
        $svg = "<svg class=\"icon icon-{$icon}\"{$aria_hidden}{$aria_labelledby}{$focusable}{$filter}{$style}{$width_attr}{$height_attr} role=\"img\">";

        // Display the title.
        if ($args['title']) {
            $svg .= '<title id="title-' . str_replace(" ", "-", $args['title']) . '-' . $unique_id . '">' . esc_html($args['title']) . '</title>';
            // Display the desc only if the title is already set.
            if ($args['desc']) $svg .= '<desc id="desc-'.$unique_id.'">'.esc_html($args['desc']).'</desc>';
        }

        /**
         * Display the icon.
         *
         * The whitespace around `<use>` is intentional - it is a work around to a keyboard navigation bug in Safari 10.
         *
         * See https://core.trac.wordpress.org/ticket/38387.
         *
         * Requires the icon sprite (resources/views/components) to be present on the page.
         */
        $svg .= ' <use href="#icon-' . esc_html( $args['icon'] ) . '" xlink:href="#icon-' . esc_html( $args['icon'] ) . '"></use> ';
        // Add some markup to use as a fallback for browsers that do not support SVGs.
        if ($args['fallback']) $svg .= '<span class="svg-fallback icon-' . esc_attr( $args['icon'] ) . '"></span>';
        $svg .= '</svg>';
        return $svg;
    }

    /**
     * Icon select options parsed from an SVG sprite Blade partial
     * (symbol ids like `icon-check` → value `check`).
     *
     * @param string $relativePath Path under the theme directory.
     * @return array<int, array{value: string, label: string}>
     */
    public function getSpriteIconOptions(string $relativePath = 'resources/views/components/footer-icons.blade.php'): array
    {
        $ids = $this->getSpriteIconIds($relativePath);
        $options = [];

        foreach ($ids as $id) {
            $options[] = [
                'value' => $id,
                'label' => ucwords(str_replace('-', ' ', $id)),
            ];
        }

        return $options;
    }

    /**
     * Symbol ids from an SVG sprite Blade partial (`icon-check` → `check`).
     *
     * @return list<string>
     */
    public function getSpriteIconIds(string $relativePath = 'resources/views/components/footer-icons.blade.php'): array
    {
        static $cache = [];

        if (isset($cache[$relativePath])) {
            return $cache[$relativePath];
        }

        $path = get_stylesheet_directory() . '/' . ltrim($relativePath, '/');

        if (! is_readable($path)) {
            return $cache[$relativePath] = [];
        }

        $html = (string) file_get_contents($path);

        if (! preg_match_all('/id="icon-([^"]+)"/', $html, $matches)) {
            return $cache[$relativePath] = [];
        }

        return $cache[$relativePath] = array_values($matches[1]);
    }

    /**
     * Whether a sprite symbol exists for the given icon id.
     */
    public function hasSpriteIcon(string $icon, string $relativePath = 'resources/views/components/footer-icons.blade.php'): bool
    {
        return in_array($icon, $this->getSpriteIconIds($relativePath), true);
    }

    /**
     * Resolve a post card icon from the first category name.
     *
     * Category name → lowercase kebab slug; if that sprite exists, use it,
     * otherwise fall back to `globe`.
     */
    public function getPostCategoryIcon(int $post_id = 0, string $fallback = 'globe'): string
    {
        $categories = get_the_category($post_id ?: false);

        if (empty($categories[0]->name)) {
            return $fallback;
        }

        $slug = strtolower((string) $categories[0]->name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        if ($slug !== '' && $this->hasSpriteIcon($slug)) {
            return $slug;
        }

        return $fallback;
    }

    /**
     * Returns an array of supported social links and brands.
     *
     * @return array $brandIcons
     */
    public function brandIcons() {
        // Supported brand links icons.
        $brand_icons = [
            'fb.me'             => 'facebook',
            'facebook.com'      => 'facebook',
            'facebook.co.uk'    => 'facebook',
//        'foursquare.com'    => 'foursquare',
//        'plus.google.com'   => 'google-plus',
            'instagram.com'     => 'instagram',
            'instagram.co.uk'   => 'instagram',
            'linkedin.com'      => 'linkedin',
//        'mailto:'           => 'email',
//        'pinterest.com'     => 'pinterest-p',
//        'skype.com'         => 'skype',
//        'skype:'            => 'skype',
            'twitter.com'       => 'x',
            'x.com'             => 'x',
            'tiktok.com'        => 'tiktok',
//        'twitch.com'        => 'twitch',
//        'vimeo.com'         => 'vimeo',
//        'yelp.com'          => 'yelp',
            'youtube.com'       => 'youtube',
            'youtube.co.uk'     => 'youtube',
            'youtu.be'          => 'youtube',
            'substack.com'      => 'substack',
            'bsky.app'          => 'bluesky',
            'linktr.ee'         => 'globe',
            'edynathan.com'     => 'globe',
        ];

        return apply_filters('brand_icons', $brand_icons);
    }
}
