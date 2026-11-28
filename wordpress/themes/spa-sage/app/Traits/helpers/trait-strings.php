<?php

namespace App\Helpers\Traits;

use WP_Query;

trait strings
{
    /**
     * Check if in the editor and if new block, to apply preview content
     * @param $field ,
     * @param $placeholder
     * @return array|string
     */
    public function checkIfEditor($field, $placeholder) : array|string
    {
        if (!function_exists('get_current_screen')) return $field;
        return $field ?: $placeholder;
    }

    /**
     * Checking if all acf fields are not set or empty
     * @param array $fields
     * @return boolean
     */
    public function checkIfValue(array $fields) : bool
    {
        return !empty(array_filter($fields));
    }

    /**
     * Check if a section should be animated or not
     * @param bool $animated
     * @return string
     */
    public function animatedSection(bool $animated = false) : string
    {
        return $animated ? 'anm8' : '';
    }

    /**
     * Transform a string into kebab-case
     *
     * @param $text
     * @return string
     */
    public function kebabCase($text) : string
    {
        $final = str_replace(' ', '-', strtolower($text));
        $final = str_ireplace( ['#038;', '&'], '', $final);
        return preg_replace('/-+/', '-', $final);
    }

    /**
     * Transform an array of strings and trim multiple separator characters
     *
     * @param string $separator             default is a space
     * @param array|null $array_of_strings
     * @return string                       returns a clean string form the array of strings
     */
    public function implodeClean(string $separator = ' ', ?array $array_of_strings = []) : string
    {
        if (empty($array_of_strings)) return '';
        $pattern = preg_quote($separator, '/');
        return trim(preg_replace("/{$pattern}{$pattern}+/", $separator, implode($separator, $array_of_strings)));
    }

    /**
     * Return the sanitised tel for href:tel
     *
     * @param string $pretty_tel
     * @return string
     */
    public function getSanitisedTel(string $pretty_tel) : string
    {
        // strip the UK domestic trunk prefix "(0)" before stripping non-digits,
        // since it should be dropped when dialling with the +44 international prefix
        $tel = preg_replace('/\(0\)/', '', $pretty_tel);
        $digits = preg_replace('/[^0-9]/', '', $tel);

        return str_contains($pretty_tel, '+') ? "+{$digits}" : $digits;
    }

    /**
     * Build parent-level `--col-{n}-width-*` / `--col-{n}-grow-*` CSS for
     * the two-column `mpx/columns` layout (desktop + tablet; mobile is 100% in CSS).
     *
     * Gap used in % calcs must match the rendered column-gap, or two ~50%
     * columns overflow and wrap to full width (looks like 100% each):
     * - tablet/mobile: `grid-auto-cards-gap` hardcodes 30px below lg
     * - desktop (lg+): `--grid-gap-col` from the Columns block
     */
    public function resolveColumnsWidthStyle(array $attrs): string
    {
        $gapColDesktop = $attrs['gap_col_desktop'] ?: '20px';
        $gapColTablet = '30px';
        $parts = [];

        foreach ([1, 2] as $n) {
            foreach ([
                'tablet' => [$attrs["col_{$n}_width_tablet"] ?? '50%', $gapColTablet],
                'desktop' => [$attrs["col_{$n}_width_desktop"] ?? '50%', $gapColDesktop],
            ] as $breakpoint => [$raw, $gap]) {
                $value = $this->normalizeUnitWidth($raw);
                if (! $this->hasGridItemWidth($value)) {
                    $value = '50%';
                }

                $basis = $this->resolveGridTrackMin($value, (string) $gap);
                $parts[] = "--col-{$n}-width-{$breakpoint}: {$basis}";

                // Always emit grow so the editor never falls through to the
                // legacy `--col-grow-desktop` lookup on generic grid items.
                if (preg_match('/^-?[\d.]+(px|rem|em|ch|vw|vmin|vmax)$/', $basis)) {
                    $parts[] = "--col-{$n}-grow-{$breakpoint}: 0";
                } else {
                    $parts[] = "--col-{$n}-grow-{$breakpoint}: 1";
                }
            }
        }

        return implode('; ', $parts);
    }

    /**
     * Coerce a Blockstudio unit field (string or value/unit array) to a CSS width.
     */
    public function normalizeUnitWidth(mixed $raw): string
    {
        if (is_array($raw)) {
            if (array_key_exists('value', $raw) || array_key_exists('unit', $raw)) {
                $number = $raw['value'] ?? '';
                $unit = $raw['unit'] ?? '';

                if (is_array($number)) {
                    $number = $number['value'] ?? '';
                }

                $number = trim((string) $number);
                $unit = trim((string) $unit);

                if ($unit === 'auto' || strcasecmp($number, 'auto') === 0) {
                    return 'auto';
                }

                if ($number === '' && $unit === '') {
                    return '';
                }

                return $number . $unit;
            }

            return '';
        }

        return trim((string) $raw);
    }

    /**
     * Build inline `--col-width-*` / `--col-grow-*` CSS from optional
     * per-item width overrides (see custom/grid_item_width).
     *
     * Empty / zero values inherit the parent Grid Controls track.
     * `auto` fills remaining row space. Fixed lengths (px/rem/…) keep
     * flex-grow at 0 so they don't expand when a sibling is auto.
     */
    public function resolveGridItemWidthStyle(array $attrs, array $parentGrid = []): string
    {
        $gapColDesktop = $parentGrid['gap_col_desktop'] ?? '20px';
        $gapSmall = '20px';

        $parts = [];

        foreach ([
            'mobile' => [$attrs['width_mobile'] ?? '', $gapSmall],
            // Match `grid-auto-cards-gap` 30px below lg (same wrap risk as columns).
            'tablet' => [$attrs['width_tablet'] ?? '', '30px'],
            'desktop' => [$attrs['width_desktop'] ?? '', $gapColDesktop ?: $gapSmall],
        ] as $breakpoint => [$raw, $gap]) {
            $parts = array_merge(
                $parts,
                $this->resolveGridItemWidthParts($breakpoint, $this->normalizeUnitWidth($raw), (string) $gap)
            );
        }

        return implode('; ', $parts);
    }

    /**
     * Resolve one breakpoint of per-item width into CSS custom properties.
     *
     * @return list<string>
     */
    public function resolveGridItemWidthParts(string $breakpoint, string $value, string $gap = '20px'): array
    {
        $value = trim($value);

        if (! $this->hasGridItemWidth($value)) {
            return [];
        }

        $basis = $this->resolveGridTrackMin($value, $gap);
        $parts = ["--col-width-{$breakpoint}: {$basis}"];

        // Fixed lengths should stay put; auto/% siblings claim leftover space.
        if (preg_match('/^-?[\d.]+(px|rem|em|ch|vw|vmin|vmax)$/', $basis)) {
            $parts[] = "--col-grow-{$breakpoint}: 0";
        } elseif ($basis === 'auto') {
            $parts[] = "--col-grow-{$breakpoint}: 1";
        }

        return $parts;
    }

    /**
     * Whether a stored unit/width value should emit a per-item override.
     */
    public function hasGridItemWidth(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        if (preg_match('/^(?:0)?auto$/i', $value)) {
            return true;
        }

        return ! preg_match('/^0(%|px)?$/', $value);
    }

    /**
     * Resolve a Grid Controls width value to a CSS flex-basis (preferred min).
     * Items use flex-grow so leftover row space is shared until another fits.
     *
     * Accepts:
     * - auto / 0auto → fill remaining space
     * - percentage ("50%") → share after gaps (50% → two columns)
     * - length ("300px") → preferred width, used as-is
     * - empty → auto (parent track cleared)
     * - legacy integer column count ("3") still supported for old content
     */
    public function resolveGridTrackMin(string $value, string $gap = '20px'): string
    {
        $value = trim($value);
        $gap = trim($gap) ?: '20px';

        if ($value === '' || preg_match('/^(?:0)?auto$/i', $value)) {
            return 'auto';
        }

        // Legacy column-count values from earlier Grid Controls.
        if (preg_match('/^\d+$/', $value)) {
            $n = max(1, (int) $value);
            $gapCount = $n - 1;

            return "calc((100% - {$gapCount} * {$gap}) / {$n})";
        }

        // Percentages are treated as a preferred share of the row, so the
        // gap is subtracted proportionally: 50% → calc(50% - gap/2).
        if (preg_match('/^([\d.]+)%$/', $value, $matches)) {
            $percent = (float) $matches[1];

            if ($percent <= 0) {
                return '100%';
            }

            if ($percent >= 100) {
                return '100%';
            }

            $gapShare = 1 - ($percent / 100);

            return "calc({$percent}% - {$gap} * {$gapShare})";
        }

        if (preg_match('/^-?[\d.]+(px|rem|em|ch|vw|vmin|vmax)$/', $value)) {
            return $value;
        }

        return 'auto';
    }

    /**
     * Sets the section padding classes
     */
    public function sectionPadding(array $padding) {
        $sp = '';
        foreach ($padding as $p) {
            $sp .= " " . $p;
        }

        return $sp;
    }

    /**
     * Sets the section background colour
     */
    public function sectionBG($bg_type, $bg_colour, $bg_gradient) {
        $bg = '';
        if ($bg_type === 'bg-gradient') {
            $bg = ($bg_gradient['slug'] ?? false) ? $bg_gradient['slug'] : 'bg-gradient-primary';
        } else {
            $bg = ($bg_colour['slug'] ?? false) ? $bg_colour['slug'] : 'bg-primary';
        }

        return $bg;
    }

    /**
     * Removing spaces and special characters from string and replacing it with dashes
     */
    public function mpxCleanUpString($string) {
        // Convert the string to lowercase
        $string = strtolower($string);

        // Replace spaces with a dash
        $string = preg_replace('/\s+/', '-', $string);

        // Remove special characters ()&/"
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);

        return $string;
    }

    /**
     * Trim string length
     */
    public function mpxStringTrim($string, $length = 200) {
        $string = strip_tags($string, '');
        if (strlen($string) < $length)
            return $string;

        $final_string = substr($string, 0, $length);
        $last_space = strrpos($final_string, ' ');
        $final_string = substr($final_string, 0, $last_space);

        return $final_string . '...';
    }

    /**
     * Get content from the page that backs an archive (hero + remaining blocks).
     *
     * Resolution order:
     * 1. Assigned archive page for the post type
     *    - `post` → Settings → Reading → Posts page (`page_for_posts`)
     * 2. Published page matching `$page_title` (preferred for CPTs so the
     *    archive URL is not taken over by a same-slug page)
     * 3. CPT: published page whose slug matches the archive URL
     *    (e.g. `has_archive => 'case-studies'` → page at `/case-studies`)
     *
     * @param string|null $post_type   Post type to resolve an archive page for
     * @param string|null $page_title  Fallback page title when no archive page is found
     * @return array{hero_banner_block: string, remaining_content: string}
     */
    public function mpxGetArchivePageContent(?string $post_type = null, ?string $page_title = null): array
    {
        $hero_banner_block = '';
        $remaining_content = '';
        $page_id = $this->resolveArchivePageId($post_type, $page_title);
        $page_content = $page_id ? (string) get_post_field('post_content', $page_id) : '';

        if ($page_content !== '') {
            foreach (parse_blocks($page_content) as $block) {
                if (($block['blockName'] ?? '') === 'mpx/hero') {
                    $hero_banner_block = render_block($block);
                } else {
                    $remaining_content .= render_block($block);
                }
            }
        }

        return [
            'hero_banner_block' => $hero_banner_block,
            'remaining_content' => $remaining_content,
        ];
    }

    /**
     * Resolve the page ID that supplies archive template content.
     */
    public function resolveArchivePageId(?string $post_type = null, ?string $page_title = null): ?int
    {
        if (! $post_type && is_home() && ! is_front_page()) {
            $post_type = 'post';
        }

        if (! $post_type && is_post_type_archive()) {
            $queried = get_query_var('post_type');
            $post_type = is_array($queried) ? ($queried[0] ?? null) : $queried;
        }

        if ($post_type) {
            // Built-in posts page (Settings → Reading).
            if ($post_type === 'post') {
                $page_id = (int) get_option('page_for_posts');
                if ($page_id > 0) {
                    return $page_id;
                }
            }
        }

        // Prefer an explicit title match before slug lookup so CPT archives
        // (e.g. /podcasts/) are not blocked by a published page at the same path.
        if ($page_title) {
            $query = new WP_Query([
                'post_type' => 'page',
                'title' => $page_title,
                'posts_per_page' => 1,
                'post_status' => 'publish',
                'no_found_rows' => true,
            ]);

            if (! empty($query->posts[0]->ID)) {
                $page_id = (int) $query->posts[0]->ID;
                wp_reset_postdata();

                return $page_id;
            }

            wp_reset_postdata();
        }

        if ($post_type && $post_type !== 'post') {
            // CPT: optional page whose slug matches the archive URL.
            $slug = $this->getPostTypeArchiveSlug($post_type);
            if ($slug) {
                $page = get_page_by_path($slug);

                if ($page instanceof \WP_Post) {
                    return (int) $page->ID;
                }
            }
        }

        return null;
    }

    /**
     * Archive URL slug for a post type, matching WordPress rewrite rules.
     *
     * Examples for `case-study`:
     * - `has_archive => 'case-studies'` → `case-studies`
     * - `has_archive => true` + `rewrite.slug => 'case-studies'` → `case-studies`
     * - `has_archive => true` (no custom rewrite) → `case-study`
     */
    public function getPostTypeArchiveSlug(string $post_type): ?string
    {
        $pto = get_post_type_object($post_type);

        if (! $pto || ! $pto->has_archive) {
            return null;
        }

        // Same precedence as WP core when registering archive rewrites.
        if (is_string($pto->has_archive) && $pto->has_archive !== '') {
            return trim($pto->has_archive, '/');
        }

        if (is_array($pto->rewrite) && ! empty($pto->rewrite['slug'])) {
            return trim((string) $pto->rewrite['slug'], '/');
        }

        return $post_type;
    }

    /**
     * Render post content with heading anchors and a table of contents.
     *
     * Collects h2/h3 headings, ensures each has a unique id, and returns
     * filtered HTML plus a flat TOC list for the single-post sidebar.
     *
     * @return array{content: string, toc: list<array{id: string, title: string, level: int}>}
     */
    public function getContentWithToc(?string $content = null): array
    {
        $html = $content ?? (string) get_the_content();
        $html = apply_filters('the_content', $html);
        $html = str_replace(']]>', ']]&gt;', $html);

        $toc = [];
        $used_ids = [];

        $html = preg_replace_callback(
            '/<h([23])(\b[^>]*)>(.*?)<\/h\1>/is',
            function (array $matches) use (&$toc, &$used_ids) {
                $level = (int) $matches[1];
                $attrs = $matches[2];
                $inner = $matches[3];
                $title = trim(wp_strip_all_tags($inner));

                if ($title === '') {
                    return $matches[0];
                }

                if (preg_match('/\bid\s*=\s*(["\'])(.*?)\1/i', $attrs, $id_match)) {
                    $id = $id_match[2];
                } else {
                    $id = sanitize_title($title) ?: ('section-' . (count($toc) + 1));
                    $base = $id;
                    $n = 2;

                    while (isset($used_ids[$id])) {
                        $id = $base . '-' . $n++;
                    }

                    $attrs .= ' id="' . esc_attr($id) . '"';
                }

                $used_ids[$id] = true;
                $toc[] = [
                    'id' => $id,
                    'title' => $title,
                    'level' => $level,
                ];

                return "<h{$level}{$attrs}>{$inner}</h{$level}>";
            },
            $html
        ) ?? $html;

        return [
            'content' => $html,
            'toc' => $toc,
        ];
    }

    /**
     * Latest posts related to the current post.
     *
     * Prefers same-category posts, then fills with the latest other posts
     * so the section still shows when nothing shares a category.
     *
     * @return \WP_Post[]
     */
    public function getRelatedPosts(int $post_id = 0, int $count = 3): array
    {
        $post_id = $post_id ?: (int) get_the_ID();

        if ($post_id < 1 || $count < 1) {
            return [];
        }

        $exclude = [$post_id];
        $related = [];

        $category_ids = wp_get_post_categories($post_id, ['fields' => 'ids']);
        if (is_wp_error($category_ids)) {
            $category_ids = [];
        }
        $category_ids = array_values(array_filter(array_map('intval', (array) $category_ids)));

        if ($category_ids) {
            $related = $this->queryRelatedPosts([
                'post__not_in' => $exclude,
                'posts_per_page' => $count,
                'category__in' => $category_ids,
            ]);

            foreach ($related as $post) {
                $exclude[] = (int) $post->ID;
            }
        }

        $remaining = $count - count($related);

        if ($remaining > 0) {
            $related = array_merge(
                $related,
                $this->queryRelatedPosts([
                    'post__not_in' => $exclude,
                    'posts_per_page' => $remaining,
                ])
            );
        }

        return $related;
    }

    /**
     * @param array<string, mixed> $args
     * @return \WP_Post[]
     */
    private function queryRelatedPosts(array $args): array
    {
        $query = new WP_Query(array_merge([
            'post_type' => 'post',
            'post_status' => 'publish',
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
            'orderby' => 'date',
            'order' => 'DESC',
        ], $args));

        return $query->posts ?: [];
    }

    /**
     * Render a registered block pattern (or synced wp_block) by name or title.
     *
     * Resolution order:
     * 1. Exact pattern name in WP_Block_Patterns_Registry
     * 2. `blockstudio/{name}` (file-based Blockstudio patterns)
     * 3. Case-insensitive title match among registered patterns
     * 4. Synced pattern / reusable block (`wp_block`) matching the title
     */
    public function renderBlockPattern(string $name_or_title): string
    {
        $content = $this->resolveBlockPatternContent($name_or_title);

        return $content !== '' ? do_blocks($content) : '';
    }

    /**
     * Resolve raw block markup for a pattern name or title.
     */
    public function resolveBlockPatternContent(string $name_or_title): string
    {
        $name_or_title = trim($name_or_title);

        if ($name_or_title === '' || ! class_exists(\WP_Block_Patterns_Registry::class)) {
            return '';
        }

        $registry = \WP_Block_Patterns_Registry::get_instance();

        if ($registry->is_registered($name_or_title)) {
            return (string) ($registry->get_registered($name_or_title)['content'] ?? '');
        }

        $blockstudio_name = 'blockstudio/' . ltrim($name_or_title, '/');
        if ($registry->is_registered($blockstudio_name)) {
            return (string) ($registry->get_registered($blockstudio_name)['content'] ?? '');
        }

        foreach ($registry->get_all_registered() as $pattern) {
            if (strcasecmp((string) ($pattern['title'] ?? ''), $name_or_title) === 0) {
                return (string) ($pattern['content'] ?? '');
            }
        }

        $query = new WP_Query([
            'post_type' => 'wp_block',
            'title' => $name_or_title,
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'no_found_rows' => true,
        ]);

        if (! empty($query->posts[0]->post_content)) {
            $content = (string) $query->posts[0]->post_content;
            wp_reset_postdata();

            return $content;
        }

        wp_reset_postdata();

        return '';
    }

    /**
     * Create Background Gradient
     */
    public function bgGradient($values) {
        return "style='background: linear-gradient(" . $values['bg_gradient_radius'] . "deg, " . $values['bg_colour_1']['value'] . " " . $values['bg_gradient_1_size'] . "%, " . $values['bg_colour_2']['value'] . " " . $values['bg_gradient_2_size'] . "%)';";
    }
}
