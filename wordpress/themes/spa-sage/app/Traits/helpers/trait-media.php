<?php

namespace App\Helpers\Traits;

trait media {
    /**
     * Site logo
     *
     * /wp-includes/general-template.php
     * @param $args[
     *      size mixed ,
     *      link_classes string,
     *      src url,
     *      custom_logo_class string,
     *      logo_variant 'default'|'alt'
     * ]
     * @return string
     */
    public function getSiteLogo (array $args = []) : string {
        $atts = array_merge([
            'logo_size'         => 'logo',
            'link_classes'      => 'navbar-brand',
            'link_url'          => esc_url(home_url('/')),
            'logo_src'          => 'logo.png',
            'custom_logo_class' => 'static site-logo',
            'logo_variant'      => 'default',
        ], $args);

        // the custom class(es), image source and image size for the <img> tag
        $logo_src           = ($atts['logo_src']) ? $atts['logo_src'] : 'logo.png';
        $logo_size          = ($atts['logo_size'] == 'logo') ? ['200', '80'] : $atts['logo_size'];
        $custom_logo_class  = $atts['custom_logo_class'];
        $custom_logo_id     = get_theme_mod('custom_logo');
        $custom_logo_alt_id = get_theme_mod('custom_logo_alt');
        $site_logo          = '';

        /**
         * The alt logo is optional in the Customizer, so requesting it
         * (`logo_variant: 'alt'`) without one having been uploaded falls
         * back to the default logo instead of rendering nothing.
         */
        $use_alt = ('alt' === $atts['logo_variant']) && $custom_logo_alt_id;
        $attachment_id = $use_alt ? $custom_logo_alt_id : $custom_logo_id;

        // Logo width from Customizer (default must match the setting default).
        // Plain numbers → Utopia-style fluid clamp between 767px and 1300px
        // viewports: min = ⅔ of value, max = full value.
        //   clamp(MINrem, INTERCEPTrem + SLOPEvw, MAXrem)
        $customizer_lw = trim((string) get_theme_mod('logo_width', '150'));
        if (is_numeric($customizer_lw)) {
            $max_px = (float) $customizer_lw;
            $min_px = $max_px * 2 / 3;
            $min_vw = 767.0;
            $max_vw = 1300.0;
            $slope = ($max_px - $min_px) / ($max_vw - $min_vw); // px per viewport-px
            $slope_vw = $slope * 100; // coefficient for `Nvw`
            $intercept_px = $min_px - $slope * $min_vw;

            $min_rem = round($min_px / 16, 4);
            $max_rem = round($max_px / 16, 4);
            $intercept_rem = round($intercept_px / 16, 4);
            $slope_vw = round($slope_vw, 4);

            $logo_style = sprintf(
                '--logo-width: %spx; --logo-width-min: %spx; width: clamp(%srem, %srem + %svw, %srem); height: auto;',
                $max_px,
                $min_px,
                $min_rem,
                $intercept_rem,
                $slope_vw,
                $max_rem
            );
        } else {
            // Non-numeric (e.g. an author-supplied clamp()) — use as-is.
            $logo_style = "--logo-width: {$customizer_lw}; width: {$customizer_lw}; height: auto;";
        }

        // render whichever logo (default or alt) was resolved above, or an empty string
        if ($attachment_id) {
            $classes   = $use_alt ? "$custom_logo_class $custom_logo_class-alt" : "$custom_logo_class main-logo";
            $symbol_id = $use_alt ? 'site-logo-alt-symbol' : 'site-logo-symbol';
            $site_logo = $this->renderLogoImage($attachment_id, $classes, $symbol_id, $logo_size, $logo_style);
        }

        // the custom class(es) and url for the wrapping link tag <a><img></a>
        $link_classes   = $atts['link_classes'];
        $link_url       = $atts['link_url'];
        $link_text      = get_bloginfo('name', 'display');

        // only wrap the link text in a screen-reader span if there's a logo to show
        $link_text      = empty($site_logo) ? $link_text : "<span class='sr-only'>{$link_text}</span>";

        // the final html
        $html = "<a href=\"{$link_url}\" class=\"{$link_classes}\" rel=\"home\">{$site_logo}{$link_text}</a>";

        // reuses WP core's own 'get_custom_logo' filter name/signature for compatibility
        return apply_filters('get_custom_logo', $html, 0);
    }

    /**
     * @param $image_id
     * @return bool
     */
    public function isSvg($image_id) {
        return 'image/svg+xml' === get_post_mime_type($image_id);
    }

    /**
     * Render a single logo <img> (raster) or sprite <svg><use> (SVG).
     *
     * SVGs are rendered as `<use href="#{$symbol_id}">` against a sprite
     * `<symbol>` (see attachmentSvgToSymbol()) rather than `<img
     * src="logo.svg">`, so the mark keeps its intrinsic fills (ECOA is
     * two-tone). White contexts use the Customizer alt logo (footer /
     * off-canvas) or a CSS filter on the scrolled header bar.
     *
     * The <svg> itself is marked decorative (aria-hidden/focusable="false")
     * since getSiteLogo() already gives the surrounding link an accessible
     * name via a sr-only site-name span - without this, the logo would be
     * announced twice.
     *
     * @param int    $attachment_id
     * @param string $classes    Class(es) for the rendered <img>/<svg>.
     * @param string $symbol_id  Sprite <symbol> id (SVGs only).
     * @param array  $size       wp_get_attachment_image() size (raster only).
     * @param string $style      Inline style, e.g. the Customizer width.
     * @return string
     */
    private function renderLogoImage(int $attachment_id, string $classes, string $symbol_id, array $size, string $style) : string {
        if (! $this->isSvg($attachment_id)) {
            return wp_get_attachment_image(
                $attachment_id,
                $size,
                false,
                [
                    'class'    => $classes,
                    'alt'      => get_bloginfo('name', 'display'),
                    'itemprop' => 'logo',
                    'style'    => $style,
                    'loading'  => 'eager',
                ]
            );
        }

        [$symbol, $view_box] = $this->attachmentSvgToSymbol($attachment_id, $symbol_id);
        if (! $view_box) {
            return '';
        }

        // A bare <symbol> outside an <svg> ancestor doesn't parse as SVG in
        // an HTML document, hence the wrapping sprite <svg> here - same
        // pattern as resources/views/components/header-icons.blade.php.
        $sprite = $symbol ? '<svg aria-hidden="true" style="position: absolute; width: 0; height: 0; overflow: hidden;">' . $symbol . '</svg>' : '';

        /**
         * The visible <svg> needs the same viewBox as the <symbol> it's
         * using, not just the <symbol> itself - without it, this <svg> has
         * no intrinsic aspect ratio of its own for `height: auto` (from
         * $style) to resolve against, so it falls back to the CSS default
         * replaced-element size (~150px tall) regardless of the actual
         * logo's proportions, squashing it into the vertical middle of a
         * too-tall box - visually indistinguishable from huge top/bottom
         * padding on the logo.
         */
        return sprintf(
            '%s<svg class="%s" itemprop="logo" viewBox="%s" style="%s" aria-hidden="true" focusable="false"><use href="#%s"></use></svg>',
            $sprite,
            esc_attr($classes),
            esc_attr($view_box),
            esc_attr($style),
            esc_attr($symbol_id)
        );
    }

    /**
     * Turn an uploaded SVG attachment into `<symbol id="$symbol_id">`
     * markup for a sprite sheet, for use via `<use href="#$symbol_id">`.
     *
     * Sanitises the file before echoing it into the page: logo uploads
     * require `edit_theme_options`, but the file on disk could still be
     * swapped out later by anything with filesystem/FTP access, so this
     * doesn't assume it's still safe to trust by the time it's rendered -
     * strips <script> tags, inline event handler attributes (onload,
     * onclick, ...), and javascript: URLs. XXE is avoided by simply never
     * enabling entity substitution/external entity loading on the
     * DOMDocument below (both are already off by default).
     *
     * Cached per request (per $symbol_id) via a static array, since:
     *  a) reading + parsing the file is wasted work beyond the first call
     *     for the same symbol in a given request, and
     *  b) a duplicate `<symbol id="...">` later in the page would be
     *     invalid HTML - so later calls return '' for the symbol markup;
     *     the caller's `<use>` reference still resolves fine against the
     *     one already rendered. The viewBox, unlike the markup, is still
     *     returned on every call (from cache) - the visible <svg> wrapping
     *     each `<use>` needs it every time, not just the first.
     *
     * @param int    $attachment_id
     * @param string $symbol_id
     * @return array{0: string, 1: string}   [symbol markup ('' if already rendered this request, or on failure), viewBox ('' on failure)]
     */
    public function attachmentSvgToSymbol(int $attachment_id, string $symbol_id) : array {
        static $view_boxes = [];

        if (array_key_exists($symbol_id, $view_boxes)) {
            // Already parsed this request - symbol already emitted, but the viewBox is still needed by every caller.
            return ['', $view_boxes[$symbol_id]];
        }

        $file = get_attached_file($attachment_id);
        $svg  = ($file && file_exists($file)) ? file_get_contents($file) : false;

        if (! $svg) {
            $view_boxes[$symbol_id] = '';

            return ['', ''];
        }

        $svg = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $svg);
        $svg = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $svg);
        $svg = preg_replace('/((?:xlink:)?href)\s*=\s*(["\'])\s*javascript:[^"\']*\2/i', '', $svg);

        $doc = new \DOMDocument();
        $previous_errors = libxml_use_internal_errors(true);
        $loaded = $doc->loadXML($svg, LIBXML_NONET);
        libxml_use_internal_errors($previous_errors);

        $root = $loaded ? $doc->documentElement : null;
        if (! $root || 'svg' !== strtolower($root->localName)) {
            $view_boxes[$symbol_id] = '';

            return ['', ''];
        }

        $view_box = $root->getAttribute('viewBox');
        if (! $view_box) {
            $width  = $root->getAttribute('width');
            $height = $root->getAttribute('height');
            $view_box = ($width && $height) ? "0 0 {$width} {$height}" : '0 0 100 100';
        }
        $view_boxes[$symbol_id] = $view_box;

        $inner = '';
        foreach ($root->childNodes as $node) {
            $inner .= $doc->saveXML($node);
        }

        return [sprintf('<symbol id="%s" viewBox="%s">%s</symbol>', esc_attr($symbol_id), esc_attr($view_box), $inner), $view_box];
    }

    /**
     * Get an HTML img element representing an image attachment
     *
     * Filters:         wp_get_attachment_image()
     * Uses filter:     wp_calculate_image_srcset_meta
     *
     * returns wp_get_attachment_image without a srcset
     * @param int          $attachment_id Image attachment ID.
     * @param string|array $size          (Optional)
     * @param bool         $icon          (Optional)
     * @param string|array $attr          (Optional)
     * @return string      HTML img element or empty string.
     */
    public function getAttachmentImageHtmlNoSrcset(int $attachment_id, $size = 'thumbnail', bool $icon = false, $attr = '') : string {
        // add a filter to return null for srcset
        add_filter( 'wp_calculate_image_srcset_meta', '__return_null' );
        // get the srcset-less img html
        $html = wp_get_attachment_image($attachment_id, $size, $icon, $attr);
        // remove the above filter
        remove_filter( 'wp_calculate_image_srcset_meta', '__return_null' );
        return $html;
    }

    /**
     * Get an HTML img element representing an image attachment
     *
     * Filters:         wp_get_attachment_image()       via getAttachmentImageHtmlNoSrcset()
     * Uses filter:     wp_calculate_image_srcset_meta  via getAttachmentImageHtmlNoSrcset()
     *
     * returns wp_get_attachment_image without a srcset, and width and height attributes
     * @param int          $attachment_id Image attachment ID.
     * @param string|array $size          (Optional)
     * @param bool         $icon          (Optional)
     * @param string|array $attr          (Optional)
     * @return string      HTML img element or empty string.
     */
    public function getAttachmentImageHtmlNoSrcsetOrHw(int $attachment_id, $size = 'thumbnail', bool $icon = false, $attr = '') : string {
        // get image html
        $html = $this->getAttachmentImageHtmlNoSrcset($attachment_id, $size, $icon, $attr);
        // remove width and height atts
        return $this->removeImageHwAtts($html);
    }

    /**
     * Removes the width and height attributes from an image tag
     *
     * Useful for scenarios where the image is presented based on other factors, such as within <picture>
     *
     * @param string $html      the <img> html tag
     * @return string $html     the <img> html tag without width="" and height=""
     */
    public function removeImageHwAtts($html) {
        return preg_replace('/(width|height)=(\"|\')\d*(\"|\')/', '', $html);
    }

    /**
     * Calculate the aspect ratio of any WordPress media library image or post thumbnail
     *
     * @param int $id The Attachment ID or Post ID.
     * @param string $max_ratio Optional. The squarest allowed ratio string (e.g. '4/3').
     * @param string|array $size Optional. Image size. Default 'full'.
     * @param bool $as_style_prop Optional. If true, returns 'aspect-ratio: W / H;' otherwise just 'W / H'. Default false
     * @return string
     *@uses \App\Helpers\Traits\media::calculateRatio()
     *
     */
    public function calculateImageRatio(int $id, string $max_ratio = '', $size = 'full', bool $as_style_prop = true) : string {
        // 1. Resolve whether the ID is an attachment or a post
        $attachment_id = get_post_type($id) === 'attachment' ? $id : get_post_thumbnail_id($id);

        // Abort early if we cannot resolve a valid attachment ID
        if (!$attachment_id) return '';

        // 2. Fetch image dimensions and calculate ratio (or abort)
        $image_data = wp_get_attachment_image_src($attachment_id, $size);
        if (!$image_data) return '';
        $w = $image_data[1] ?? false;
        $h = $image_data[2] ?? false;
        if (!$w || !$h) return '';

        return $this->calculateRatio($w, $h, $as_style_prop, $max_ratio);
    }

    /**
     * Calculate the aspect ratio and return it as a CSS property or raw ratio
     *
     * @param int|float|string $w Width
     * @param int|float|string $h Height
     * @param bool $as_style_prop Optional. If true, returns 'aspect-ratio: W / H;' otherwise just 'W / H'. Default false
     * @param string $max_ratio Optional. The squarest allowed ratio string (e.g. '4/3'). Default ''.
     * @return string
     */
    public function calculateRatio($w, $h, bool $as_style_prop = false, string $max_ratio = '') : string {
        // Return empty if dimensions are invalid or missing
        if (!is_numeric($w) || !is_numeric($h) || (float)$h === 0) return '';
        $image_ratio = (float)$w / (float)$h;
        $final_ratio = "{$w} / {$h}";

        // If a boundary is set, check if the image is squarer than the limit
        if (!empty($max_ratio)) {
            $parts = explode('/', $max_ratio);
            if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1]) && (float)$parts[1] !== 0) {
                $limit_ratio = (float)$parts[0] / (float)$parts[1];

                // If the image ratio is lower, it is squarer than the limit
                if ($image_ratio < $limit_ratio) $final_ratio = trim($max_ratio);
            }
        }

        return $as_style_prop ? "aspect-ratio: {$final_ratio};" : $final_ratio;
    }

    /**
     * Wrapper for wp_get_attachment_image() that adds a 'static' class
     *
     * @param $attachment_id
     * @param string $size
     * @param bool $icon
     * @param string|array $attr
     * @return string
     */
    public function getAttachmentImageStatic($attachment_id, $size = 'thumbnail', $icon = false, $attr = '') {
        return wp_get_attachment_image($attachment_id, $size, $icon, wp_parse_args($attr, ['class' => 'static']));
    }
}
