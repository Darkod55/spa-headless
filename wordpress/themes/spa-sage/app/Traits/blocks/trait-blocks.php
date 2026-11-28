<?php

namespace App\MpxBlocks\Traits;

trait blocks
{
    public bool $is_editor_mode;

    /**
     * Adding the MPX block categories and positioning them at the top of the list.
     */
    public function addBlocksCategory()
    {
        add_filter('block_categories_all', function ($categories) {
            return array_merge(
                [
                    ['slug' => 'spa', 'title' => __('Square Peg', 'sage')],
                    ['slug' => 'spa-partials', 'title' => __('Square Peg partials', 'sage')],
                ],
                $categories
            );
        });
    }

    /**
     * Keep core block CSS on the combined library handle.
     *
     * SetupPlus dequeues that handle (and any leftover wp-block-* styles) on
     * the front end — enabling separate/on-demand assets would re-inject
     * per-block inline <style> tags that bypass the old dequeue.
     */
    public function loadingBlockAssets()
    {
        add_filter('should_load_separate_core_block_assets', '__return_false');
    }

    /**
     * Creating Pattern Categories
     */
    public function createPatternCategories()
    {
        add_action('init', function() {

            register_block_pattern_category(
                'spa-global',
                [
                    'label'       => _x( 'Global', 'Block pattern category' ),
                    'description' => __( 'A collection of global blocks' )
                ]
            );
        });
    }

    /**
     * Render `.blade.php` block templates through Acorn's existing Blade
     * engine (the same one Sage uses for resources/views), instead of the
     * separate jenssegers/blade instance Blockstudio's own docs set up.
     *
     * This reuses the theme's registered Blade directives (e.g. @svgIcon
     * from ThemeServiceProvider), one shared compiled-view cache, and lets
     * block templates @include/@extend anything under resources/views if
     * ever needed - no extra Composer dependency required.
     *
     * Blockstudio's `blockstudio/blocks/render` filter fires for every
     * block regardless of engine; the plugin's own PHP-include renderer
     * runs first for .blade.php files too (Blade syntax just passes
     * through untouched, since it isn't valid PHP), so we detect the
     * `.blade.php` path here and substitute the real Blade-rendered
     * markup instead. Blockstudio then re-resolves any <RichText>,
     * <InnerBlocks>, or useBlockProps pseudo-components found in our
     * returned string, exactly as it does for native PHP/Twig templates.
     */
    public function renderBladeBlockTemplates() {
        add_filter('blockstudio/blocks/render', function ($content, $filterData, $isEditor, $isPreview) {
            $data = $filterData->blockstudio['data'] ?? null;
            $path = $data['path'] ?? '';

            if (! $data || ! str_ends_with($path, '.blade.php')) {
                return $content;
            }

            return (string) view()->file($path, [
                'a' => $data['attributes'],
                'attributes' => $data['attributes'],
                'b' => $data['block'],
                'block' => $data['block'],
                'c' => $data['context'],
                'context' => $data['context'],
                'isEditor' => $isEditor,
                'isPreview' => $isPreview,
            ]);
        }, 10, 4);
    }

    /**
     * Modify the path where Blockstudio looks for custom blocks.
     *
     * Blockstudio's own bootstrap already calls `Build::init()` on `init`
     * for its default `{theme}/blockstudio` directory, and that call reads
     * this filter (`blockstudio/path`) to decide where to look instead -
     * see `Blockstudio\Build::get_build_dir()`. Using the filter (rather
     * than a second, unconditional `Build::init()` call here) avoids
     * registering a redundant instance pointed at a `blockstudio/` folder
     * that doesn't exist in this theme.
     */
    public function mpxBlockstudioPath() {
        add_filter('blockstudio/path', function () {
            return get_stylesheet_directory() . '/app/blocks';
        });
    }

    /**
     * Frontend CSS is owned entirely by Vite (app.css). Editor-added spacing
     * utilities (mt-12, py-4, etc.) are safelisted in
     * resources/css/theme/editor-safelist.css so they compile at build time
     * without Blockstudio injecting a second inline stylesheet on every request.
     *
     * Keep tailwind/enabled in wp-admin for the block editor CDN +
     * compile_editor_css(). Do not disable blockstudio/buffer/enabled — that
     * buffer also hoists per-block assets.
     */
    public function disableFrontendBlockstudioTailwind() {
        add_filter('blockstudio/settings/tailwind/enabled', function ($enabled) {
            if (! is_admin()) {
                return false;
            }

            return $enabled;
        });
    }

    /**
     * Bridge our theme's design tokens into Blockstudio's editor Tailwind
     * compiler so the canvas preview stays in sync with the theme's real
     * @theme config instead of only using Tailwind defaults.
     *
     * Editor-only: the public site loads the same tokens via Vite app.css.
     *
     * Globs resources/css/theme/*.css directly (variables, fonts,
     * sections, buttons, typography, ...) rather than reading theme.css
     * itself, since theme.css is just a set of @imports that Blockstudio's
     * compiler has no reason to resolve.
     *
     * CSS that must ship only via Vite (not re-emitted into Blockstudio's
     * inline <style id="blockstudio-tailwind-editor">) should live outside
     * theme/ and be @import'ed from theme.css — see
     * resources/css/components/spa-subheading.css.
     *
     * @font-face rules are stripped: font files are fingerprinted into
     * public/build by Vite; relative font urls would 404 in Blockstudio's
     * compiled CSS context.
     */
    public function bridgeTailwindConfig() {
        add_filter('blockstudio/tailwind/css', function ($css) {
            if (! is_admin()) {
                return $css;
            }

            $theme_files = glob(get_stylesheet_directory() . '/resources/css/theme/*.css') ?: [];

            foreach ($theme_files as $theme_file) {
                $contents = file_get_contents($theme_file);
                $contents = preg_replace('/@font-face\s*\{[^{}]*\}/s', '', $contents) ?: $contents;
                $css .= "\n" . $contents;
            }

            return $css;
        });
    }

    /**
     * Removing the extra wrapper for inner blocks
     */
    public function mpxRemoveInnerBlocksWrapper() {
        add_filter('blockstudio/blocks/components/innerblocks/frontend/wrap', function ($render, $block) {
            if (in_array($block->name, [
                'spa/partial-button',
                'spa/partial-buttons',
                'spa/partial-column',
                'spa/partial-subheading',
                'spa/partial-cards',
                'spa/partial-card',
                'spa/partial-card-icon',
                'spa/partial-card-image',
                'spa/partial-card-testimonial-simple',
                'spa/partial-icon',
                'spa/partial-divider',
                'spa/partial-box',
                'spa/partial-row',
                'spa/partial-checklist',
                'spa/partial-checklist-item',
                'spa/partial-gallery',
                'spa/hero',
                'spa/hero-v2',
                'spa/column-cover',
                'spa/one-column',
                'spa/columns',
                'spa/keynote',
                'spa/partners-slider',
                'spa/events',
            ], true)) {
                $render = false;
            }

            return $render;
        }, 10, 2);
    }

    /**
     * Frontend: unwrap spa/partial-button and move Advanced → Additional
     * CSS classes onto the inner .wp-block-button.
     *
     * Editor keeps useBlockProps (selection + className on the wrapper).
     * Frontend blade skips that wrapper, so className would otherwise
     * never appear — this pass applies attrs.className (and align*) to
     * the real button element instead.
     */
    public function unwrapPartialButton() {
        add_filter('render_block_spa/partial-button', function ($content, $block) {
            if ($content === '') {
                return $content;
            }

            if (str_contains($content, 'wp-block-spa-partial-button')
                && preg_match(
                    '#<div\b[^>]*\bwp-block-spa-partial-button\b[^>]*>(.*?)</div>#s',
                    $content,
                    $matches
                )
            ) {
                $content = str_replace($matches[0], trim($matches[1]), $content);
            }

            $extra = [];

            if (! empty($block['attrs']['className']) && is_string($block['attrs']['className'])) {
                $extra = array_merge(
                    $extra,
                    preg_split('/\s+/', trim($block['attrs']['className'])) ?: []
                );
            }

            if (! empty($block['attrs']['align']) && is_string($block['attrs']['align'])) {
                $extra[] = 'align' . $block['attrs']['align'];
            }

            $extra = array_values(array_filter(array_unique($extra)));

            if ($extra === [] || ! class_exists(\WP_HTML_Tag_Processor::class)) {
                return $content;
            }

            $processor = new \WP_HTML_Tag_Processor($content);

            if (! $processor->next_tag(['class_name' => 'wp-block-button'])) {
                return $content;
            }

            foreach ($extra as $class) {
                $processor->add_class($class);
            }

            return $processor->get_updated_html();
        }, 20, 2);
    }

    /**
     * Removing the is-style class from the MPX blocks
     */
    public function removeIsStyleClassFromMpxBlocks() {
        add_filter('render_block', function ($block_content, $block) {
            if (isset($block['blockName'])) {
                if ((strpos($block['blockName'], 'spa/') === 0) && (strpos($block_content, 'is-style-' ) !== false))
                    $block_content = str_replace('is-style-', '', $block_content);
            }

            return $block_content;
        }, 10, 2);
    }

    /**
     * Populating text colours for elements
     */
    /**
     * BlockStudio indexes a reusable field by the `name` inside field.json.
     * `custom/background-colour` does not match `background_colour`, so the
     * reference stays unexpanded and the inspector skips it (no id).
     * Register both keys.
     */
    public function registerColourFieldNames() {
        add_filter('blockstudio/fields', function ($fields) {
            $base = get_stylesheet_directory() . '/app/blocks/fields/';
            foreach (array('background-colour', 'text-colour') as $name) {
                $path = $base . $name . '/field.json';
                if (! is_readable($path)) {
                    continue;
                }
                $decoded = json_decode((string) file_get_contents($path), true);
                if (! is_array($decoded)) {
                    continue;
                }
                $fields[$name] = $decoded;
                if (! empty($decoded['name']) && is_string($decoded['name'])) {
                    $fields[$decoded['name']] = $decoded;
                }
            }

            return $fields;
        });
    }

    public function populateTextColours() {
        add_filter('blockstudio/blocks/attributes/populate', function ($options) {
            $surfaces = [
                ['value' => 'var(--color-primary)', 'name' => 'Primary', 'slug' => 'primary'],
                ['value' => 'var(--color-secondary)', 'name' => 'Secondary', 'slug' => 'secondary'],
                ['value' => 'var(--color-tertiary)', 'name' => 'Tertiary', 'slug' => 'tertiary'],
                ['value' => 'var(--color-white)', 'name' => 'White', 'slug' => 'white'],
                ['value' => 'var(--color-gray)', 'name' => 'Gray', 'slug' => 'gray'],
                ['value' => 'var(--color-black)', 'name' => 'Black', 'slug' => 'black'],
                ['value' => 'var(--color-secondary-deep)', 'name' => 'Secondary deep', 'slug' => 'secondary-deep'],
            ];
            $text = array_merge($surfaces, [
                ['value' => 'var(--color-secondary-on-primary)', 'name' => 'Red on navy', 'slug' => 'secondary-on-primary'],
                ['value' => 'var(--color-secondary-on-tertiary)', 'name' => 'Red on royal', 'slug' => 'secondary-on-tertiary'],
            ]);
            $options['textColours'] = $text;
            $options['tagBgColours'] = $surfaces;

            return $options;
        });
    }


    /**
     * Populating background colours for sections
     */
    public function populateBGColours() {
        add_filter('blockstudio/blocks/attributes/populate', function ($options) {
            $options['bgColours'] = [
                ['value' => 'var(--color-primary)', 'name' => 'Primary', 'slug' => 'primary'],
                ['value' => 'var(--color-secondary)', 'name' => 'Secondary', 'slug' => 'secondary'],
                ['value' => 'var(--color-tertiary)', 'name' => 'Tertiary', 'slug' => 'tertiary'],
                ['value' => 'var(--color-white)', 'name' => 'White', 'slug' => 'white'],
                ['value' => 'var(--color-gray)', 'name' => 'Gray', 'slug' => 'gray'],
                ['value' => 'var(--color-black)', 'name' => 'Black', 'slug' => 'black'],
                ['value' => 'var(--color-secondary-deep)', 'name' => 'Secondary deep', 'slug' => 'secondary-deep'],
            ];

            return $options;
        });
    }

    /**
     * Icon options for spa/partial-icon + spa/partial-card, sourced from
     * the footer-icons SVG sprite symbol ids.
     */
    public function populateSpriteIcons() {
        add_filter('blockstudio/blocks/attributes/populate', function ($options) {
            $options['spriteIcons'] = \App\helpers()->getSpriteIconOptions();

            return $options;
        });
    }

    /**
     * Populating button types
     */
    public function populateButtonTypes() {
        add_filter('blockstudio/blocks/attributes/populate', function ($options) {
            $options['buttonTypes'] = [
                ['value' => 'primary', 'label' => 'Primary'],
                ['value' => 'light', 'label' => 'Light'],
                ['value' => 'dark', 'label' => 'Dark'],
                ['value' => 'outline-light', 'label' => 'Outline'],
                ['value' => 'text', 'label' => 'Text'],
            ];

            return $options;
        });
    }

    /**
     * Restyle core/button's inner link for our .btn system.
     *
     * Blockstudio extension `set: class` targets the .wp-block-button
     * wrapper. This pass (priority 20, after Blockstudio at 10):
     *
     *  1. Resolves button_type from blockstudio attrs (selects save as
     *     `{value,label}`; default Primary when missing)
     *  2. Moves visual `btn` / `btn-*` classes onto the inner <a>
     *  3. Strips core's `wp-block-button__link` + `wp-element-button`
     *     so their unlayered CSS can't override our components
     *  4. When Include icon is on, appends the arrow-insert sprite SVG
     *     after the link text (editor uses a ::after mask instead —
     *     React owns the canvas markup)
     *
     * Layout classes (w-full, flex, justify-*) and btn-full stay on the
     * wrapper. Editor preview still has the WP classes (React render),
     * so editor.css uses unlayered overrides for the colliding props.
     */
    public function moveCoreButtonClassesToLink() {
        add_filter('render_block_core/button', function ($content, $block) {
            if ($content === '' || ! class_exists(\WP_HTML_Tag_Processor::class)) {
                return $content;
            }

            $attrs = $block['attrs']['blockstudio']['attributes'] ?? [];

            $button_type = $attrs['button_type'] ?? 'primary';
            if (is_array($button_type)) {
                $button_type = $button_type['value'] ?? 'primary';
            }
            $button_type = is_string($button_type) ? trim($button_type) : '';
            $live_types = ['primary', 'light', 'dark', 'outline-light', 'text'];
            $is_live = in_array($button_type, $live_types, true);
            if ($button_type === '') {
                $button_type = 'primary';
                $is_live = true;
            }

            if (array_key_exists('arrow', $attrs)) {
                $arrow = ! in_array($attrs['arrow'], [false, 'false', 0, '0'], true);
            } elseif (array_key_exists('include_icon', $attrs)) {
                $arrow = ! empty($attrs['include_icon']);
            } else {
                $arrow = true;
            }
            $include_icon = $arrow;

            $processor = new \WP_HTML_Tag_Processor($content);

            if (! $processor->next_tag(['class_name' => 'wp-block-button'])) {
                return $content;
            }

            $wrapper_classes = preg_split('/\s+/', trim((string) $processor->get_attribute('class'))) ?: [];
            $move = [];
            $keep = [];
            $has_btn = false;
            $has_type = false;

            foreach ($wrapper_classes as $class) {
                if ($class === '') {
                    continue;
                }

                // btn-full stays on the wrapper (sizes the flex item) AND
                // is copied onto the link (the coloured <a> must stretch too
                // — after we strip .wp-block-button__link, the child-only
                // CSS selector would otherwise miss it).
                if ($class === 'btn-full') {
                    $keep[] = $class;
                    $move[] = $class;
                    continue;
                }

                if ($class === 'cta' || str_starts_with($class, 'cta--')) {
                    $move[] = $class;
                    if ($class === 'cta') {
                        $has_btn = true;
                    }
                    if (str_starts_with($class, 'cta--') && ! in_array($class, ['cta--arrow', 'cta--no-arrow'], true)) {
                        $has_type = true;
                    }

                    continue;
                }

                if ($class === 'btn' || str_starts_with($class, 'btn-')) {
                    $move[] = $class;
                    if ($class === 'btn') {
                        $has_btn = true;
                    }
                    if (in_array($class, [
                        'btn-primary',
                        'btn-secondary',
                        'btn-secondary-inverse',
                        'btn-secondary-outline',
                        'btn-tertiary',
                        'btn-dark-blue',
                        'btn-primary-alt',
                        'btn-white',
                        'btn-white-inverse',
                        'btn-outline-primary',
                        'btn-gradient',
                    ], true)) {
                        $has_type = true;
                    }
                } else {
                    $keep[] = $class;
                }
            }

            if ($is_live) {
                if (! $has_btn) {
                    $move[] = 'cta';
                }
                if (! $has_type) {
                    $move[] = 'cta--' . $button_type;
                }
                $move[] = $arrow ? 'cta--arrow' : 'cta--no-arrow';
            } else {
                if (! $has_btn) {
                    $move[] = 'btn';
                }
                if (! $has_type) {
                    $move[] = $button_type;
                }
                if ($include_icon && ! in_array('btn-has-icon', $move, true)) {
                    $move[] = 'btn-has-icon';
                }
            }

            $move = array_values(array_unique($move));

            // Seek the link before mutating the wrapper, so a failed seek
            // can't leave btn classes stripped from both elements.
            $processor->set_bookmark('button-wrapper');

            $found_link = $processor->next_tag(['class_name' => 'wp-block-button__link']);
            if (! $found_link && $processor->has_bookmark('button-wrapper')) {
                $processor->seek('button-wrapper');
                $found_link = $processor->next_tag('a');
            }

            if (! $found_link) {
                return $content;
            }

            $strip = ['wp-block-button__link', 'wp-element-button'];
            $link_classes = preg_split('/\s+/', trim((string) $processor->get_attribute('class'))) ?: [];
            $link_classes = array_values(array_filter(
                $link_classes,
                static fn ($class) => $class !== '' && ! in_array($class, $strip, true)
            ));

            $processor->set_attribute(
                'class',
                implode(' ', array_merge($link_classes, $move))
            );

            if ($processor->has_bookmark('button-wrapper')) {
                $processor->seek('button-wrapper');
                $processor->set_attribute('class', implode(' ', $keep));
            }

            $html = $processor->get_updated_html();

            if (! $arrow || str_contains($html, 'cta__arrow') || str_contains($html, 'icon-arrow-insert')) {
                return $html;
            }

            $icon = $is_live
                ? '<svg class="cta__arrow" width="20" height="12" viewBox="0 0 20 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M0 6 H16" stroke="currentColor" stroke-width="1.8"/><path d="M12 1 L17 6 L12 11" stroke="currentColor" stroke-width="1.8" fill="none" stroke-linejoin="miter"/></svg>'
                : \App\helpers()->getSvg([
                    'icon'  => 'arrow-insert',
                    'width' => 12,
                    'title' => '',
                ]);

            return preg_replace('/<\/a>/', $icon . '</a>', $html, 1) ?: $html;
        }, 20, 2);
    }

    /**
     * Populating video type options
     */
    public function populateVideoType() {
        add_filter('blockstudio/blocks/attributes/populate', function ($options) {
            $options['videoType'] = [
                ['value' => 'self-hosted', 'label' => 'Self-Hosted'],
                ['value' => 'embed', 'label' => 'Embed']
            ];

            return $options;
        });
    }

    /**
     * Populating gradient colours for sections
     */
    public function populateGradientBGColours() {
        add_filter('blockstudio/blocks/attributes/populate', function ($options) {
            $options['bgGradient'] = [
                ['value' => 'linear-gradient(0deg, #ff8a20 0%, #ff6d2b 100%)', 'name' => 'Primary', 'slug' => 'bg-gradient-primary'],
                ['value' => 'linear-gradient(0deg, #4cd5d0 0%, #00bab3 100%)', 'name' => 'Secondary', 'slug' => 'bg-gradient-secondary'],
                ['value' => 'linear-gradient(0deg, #ffffff 0%, #d9e4e5 100%)', 'name' => 'Gray', 'slug' => 'bg-gradient-gray']
            ];

            return $options;
        });
    }

    /**
     * Populating column position options
     */
    public function populateColumnPosition() {
        add_filter('blockstudio/blocks/attributes/populate', function ($options) {
            $options['columnPosition'] = [
                ['value' => 'jc-start', 'label' => 'Start'],
                ['value' => 'jc-center', 'label' => 'Center'],
                ['value' => 'jc-end', 'label' => 'End']
            ];

            return $options;
        });
    }

    /**
     * Populating row content alignment options
     */
    public function populateRowAlignment() {
        add_filter('blockstudio/blocks/attributes/populate', function ($options) {
            $options['rowAlignment'] = [
                ['value' => '', 'label' => 'Default'],
                ['value' => 'ai-start', 'label' => 'Start'],
                ['value' => 'ai-center', 'label' => 'Center'],
                ['value' => 'ai-end', 'label' => 'End'],
                ['value' => 'ai-stretch', 'label' => 'Stretch']
            ];

            return $options;
        });
    }

    /**
     * Populating columns gap
     */
    public function populateColumnGap() {
        add_filter('blockstudio/blocks/attributes/populate', function ($options) {
            $options['columnGap'] = [
                ['value' => '', 'label' => 'None'],
                ['value' => 'g-3', 'label' => 'Small'],
                ['value' => 'g-5', 'label' => 'Large']
            ];

            return $options;
        });
    }

    /**
     * Populating row layout options
     */
    public function populateRowLayout() {
        add_filter('blockstudio/blocks/attributes/populate', function ($options) {
            $options['rowLayout'] = [
                ['value' => 'flex-row', 'label' => 'Row'],
                ['value' => 'flex-column-reverse flex-lg-row-reverse', 'label' => 'Row Reverse'],
                ['value' => 'flex-lg-row-reverse', 'label' => 'Row Reverse (only on desktop)']
            ];

            return $options;
        });
    }

    /**
     * Populating section padding
     *
     * Values map to utilities in resources/css/theme/sections.css
     * (py-clamp / py-clamp-xl) plus Tailwind spacing scale classes.
     */
    public function populateSectionPadding() {
        add_filter('blockstudio/blocks/attributes/populate', function ($options) {
            $options['sectionPadding'] = [
                ['value' => 'py-5', 'label' => 'Small (Top and Bottom)'],
                ['value' => 'py-clamp', 'label' => 'Regular (Top and Bottom)'],
                ['value' => 'py-clamp-xl', 'label' => 'Large (Top and Bottom)'],
                ['value' => 'py-0', 'label' => 'None'],
                ['value' => 'pt-0', 'label' => 'None Top'],
                ['value' => 'pb-0', 'label' => 'None Bottom'],
                ['value' => 'pt-5', 'label' => 'Small Top'],
                ['value' => 'pb-5', 'label' => 'Small Bottom'],
                ['value' => 'pt-clamp-block', 'label' => 'Regular Top'],
                ['value' => 'pb-clamp-block', 'label' => 'Regular Bottom'],
                ['value' => 'pt-clamp-block-xl', 'label' => 'Large Top'],
                ['value' => 'pb-clamp-block-xl', 'label' => 'Large Bottom'],
            ];

            return $options;
        });
    }

    /**
     * Split partners-slider gallery items into two identical groups so the
     * CSS marquee can use flex gap inside each group and still loop on an
     * exact translate of one group width. Skipped in the editor.
     */
    public function duplicatePartnersSliderGallery() {
        add_filter('render_block_spa/partners-slider', function ($content) {
            if (
                $content === ''
                || str_contains($content, 'is-editor')
                || ! str_contains($content, 'wp-block-gallery')
                || ! class_exists(\DOMDocument::class)
            ) {
                return $content;
            }

            $dom = new \DOMDocument();
            $previous = libxml_use_internal_errors(true);
            $dom->loadHTML(
                '<?xml encoding="utf-8"><div id="spa-partners-root">' . $content . '</div>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            $xpath = new \DOMXPath($dom);
            $galleries = $xpath->query(
                '//*[contains(concat(" ", normalize-space(@class), " "), " wp-block-gallery ")]'
            );

            if (! $galleries || $galleries->length === 0) {
                return $content;
            }

            foreach ($galleries as $gallery) {
                if (
                    ! $gallery instanceof \DOMElement
                    || $gallery->getAttribute('data-spa-marquee') === 'true'
                ) {
                    continue;
                }

                $children = [];
                foreach (iterator_to_array($gallery->childNodes) as $child) {
                    if ($child instanceof \DOMElement) {
                        $children[] = $child;
                    }
                }

                if ($children === []) {
                    continue;
                }

                $groupA = $dom->createElement('div');
                $groupA->setAttribute('class', 'spa-partners-group');

                $groupB = $dom->createElement('div');
                $groupB->setAttribute('class', 'spa-partners-group');
                $groupB->setAttribute('aria-hidden', 'true');

                foreach ($children as $child) {
                    $groupA->appendChild($child);
                    $groupB->appendChild($child->cloneNode(true));
                }

                $gallery->appendChild($groupA);
                $gallery->appendChild($groupB);
                $gallery->setAttribute('data-spa-marquee', 'true');
            }

            $root = $dom->getElementById('spa-partners-root');
            if (! $root) {
                return $content;
            }

            $out = '';
            foreach ($root->childNodes as $child) {
                $out .= $dom->saveHTML($child);
            }

            return $out !== '' ? $out : $content;
        });
    }


    /**
     * Populating inner content padding
     */
    public function populateInnerPadding() {
        add_filter('blockstudio/blocks/attributes/populate', function ($options) {
            $options['innerPadding'] = [
                ['value' => 'py-5', 'label' => 'Small (Top and Bottom)'],
                ['value' => 'py-clamp', 'label' => 'Regular (Top and Bottom)'],
                ['value' => 'py-clamp-xl', 'label' => 'Large (Top and Bottom)'],
                ['value' => 'py-0', 'label' => 'None'],
                ['value' => 'pt-0', 'label' => 'None Top'],
                ['value' => 'pb-0', 'label' => 'None Bottom'],
                ['value' => 'pt-5', 'label' => 'Small Top'],
                ['value' => 'pb-5', 'label' => 'Small Bottom'],
                ['value' => 'pt-clamp-block', 'label' => 'Regular Top'],
                ['value' => 'pb-clamp-block', 'label' => 'Regular Bottom'],
                ['value' => 'pt-clamp-block-xl', 'label' => 'Large Top'],
                ['value' => 'pb-clamp-block-xl', 'label' => 'Large Bottom'],
            ];

            return $options;
        });
    }

    /**
     * Populating Image Ratio options
     */
    public function imageRatios() {
        add_filter('blockstudio/blocks/attributes/populate', function ($options) {
            $options['imageRatio'] = [
                ['value' => 'none', 'label' => 'Default'],
                ['value' => '1x1', 'label' => '1:1'],
                ['value' => '4x3', 'label' => '4:3'],
                ['value' => '16x9', 'label' => '16:9'],
                ['value' => '21x9', 'label' => '21:9']
            ];

            return $options;
        });
    }

    /**
     * Populating Image Type options
     */
    public function imageTypes() {
        add_filter('blockstudio/blocks/attributes/populate', function ($options) {
            $options['imageType'] = [
                ['value' => 'img-obj-contain', 'label' => 'Contain'],
                ['value' => 'img-obj-cover', 'label' => 'Cover']
            ];

            return $options;
        });
    }


    /**
     * Populating Image Position options
     */
    public function imagePositions() {
        add_filter('blockstudio/blocks/attributes/populate', function ($options) {
            $options['imagePosition'] = [
                ['value' => 'img-obj-center', 'label' => 'Center'],
                ['value' => 'img-obj-top', 'label' => 'Top'],
                ['value' => 'img-obj-bottom', 'label' => 'Bottom'],
                ['value' => 'img-obj-left', 'label' => 'Left'],
                ['value' => 'img-obj-right', 'label' => 'Right']
            ];

            return $options;
        });
    }

    /**
     * Restyle Rank Math FAQ output as a card grid on the frontend.
     * Editor keeps the default Rank Math FAQ UI (edit.js); this filter
     * only runs when the block is rendered on the front.
     */
    public function faqWidget() {
        add_filter( 'render_block_rank-math/faq-block', function ( $block_content, $block ) {
            if ( ( $block['blockName'] ?? '' ) !== 'rank-math/faq-block' ) {
                return $block_content;
            }

            // Escape hatch: keep Rank Math's default markup.
            if ( $block['attrs']['blockstudio'] ?? false ) {
                if ( $block['attrs']['blockstudio']['attributes']['remove_accordion'] ?? false ) {
                    return $block_content;
                }
            }

            if ( empty( $block['attrs']['questions'] ) || ! is_array( $block['attrs']['questions'] ) ) {
                return $block_content;
            }

            $title_wrapper = ! empty( $block['attrs']['titleWrapper'] )
                ? tag_escape( $block['attrs']['titleWrapper'] )
                : 'h3';

            $items = '';

            foreach ( $block['attrs']['questions'] as $qa ) {
                $question_text = isset( $qa['title'] ) ? (string) $qa['title'] : '';
                $answer_text = isset( $qa['content'] ) ? (string) $qa['content'] : '';

                if ( $question_text === '' || $answer_text === '' || empty( $qa['visible'] ) ) {
                    continue;
                }

                $item_id = ! empty( $qa['id'] )
                    ? $qa['id']
                    : 'rm-faq-' . md5( $question_text );

                $answer_html = wp_kses_post( $answer_text );
                if ( $answer_html === wp_strip_all_tags( $answer_html ) ) {
                    $answer_html = wpautop( $answer_html );
                }

                if ( ! empty( $qa['imageUrl'] ) ) {
                    $answer_html = '<img src="' . esc_url( $qa['imageUrl'] ) . '" alt="" />' . $answer_html;
                }

                $items .= sprintf(
                    '<div id="%1$s" class="spa-faq-card"><%2$s class="spa-faq-card__question">%3$s</%2$s><div class="spa-faq-card__answer text-tertiary">%4$s</div></div>',
                    esc_attr( $item_id ),
                    $title_wrapper,
                    wp_kses_post( $question_text ),
                    $answer_html
                );
            }

            if ( $items === '' ) {
                return $block_content;
            }

            $bs_attrs = $block['attrs']['blockstudio']['attributes'] ?? [];
            $two_columns = (bool) ( $bs_attrs['two_columns'] ?? false );
            $white_cards = (bool) ( $bs_attrs['white_cards'] ?? false );

            $grid_classes = 'spa-faqs-grid grid-auto-cards grid-auto-cards-gap';
            if ( $two_columns ) {
                $grid_classes .= ' is-two-columns';
            }
            if ( $white_cards ) {
                $grid_classes .= ' is-white-cards';
            }

            return '<div class="' . esc_attr( $grid_classes ) . '">' . $items . '</div>';
        }, 10, 2 );
    }

    /**
     * Add editor body classes for post type and page templates.
     */
    public function addBodyClassEditor() {
        add_filter('admin_body_class', function ($classes) {
            global $post;

            $post_type = null;

            if (isset($post) && !empty($post->post_type)) {
                $post_type = $post->post_type;
            } elseif (function_exists('get_current_screen')) {
                $screen = get_current_screen();

                if ($screen && !empty($screen->post_type)) {
                    $post_type = $screen->post_type;
                }
            }

            if ($post_type) {
                $editor_slug = $post_type === 'post' ? 'posts' : $post_type;
                $classes .= ' spa-edit-' . sanitize_html_class($editor_slug);
            }

            if (isset($post) && $post->post_type === 'page') {
                $template = get_page_template_slug($post->ID);

                if ($template) {
                    $template_class = 'page-template-' . sanitize_title(basename($template, '.php'));
                    $classes .= ' ' . $template_class;
                }
            }

            return $classes;
        });
    }
}
