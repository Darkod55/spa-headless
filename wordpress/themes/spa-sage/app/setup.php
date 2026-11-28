<?php

/**
 * Theme setup.
 */

namespace App;

use Illuminate\Support\Facades\Vite;

/**
 * Inject styles into the block editor.
 *
 * @return array
 */
add_filter('block_editor_settings_all', function ($settings) {
    $style = Vite::asset('resources/css/editor.css');

    $settings['styles'][] = [
        'css' => "@import url('{$style}')",
    ];

    return $settings;
});

/**
 * Strip block "supports" flags that have no matching theme.json `settings`
 * toggle, so they can't be re-enabled per block from the "+" menu in the
 * Styles tab regardless of what's disabled in theme.json.
 *
 * Most typography/color/spacing features (font size, line height, custom
 * colours, etc.) are controlled by *both* a block's `supports` flag *and*
 * a theme.json `settings` value - disabling the setting hides the control
 * even though the block still declares support for it. A few flags (like
 * `typography.fitText` here) have no corresponding settings key at all,
 * so they keep showing up in the block's Styles panel no matter what
 * theme.json says. The only way to remove those is to unset the support
 * itself, before the block type is registered.
 *
 * For `core/button`, we also clear the built-in Fill/Outline styles and
 * every Styles-tab support (typography, shadow, colour, spacing, border)
 * so the Styles tab disappears entirely - button look/width is owned by
 * the Blockstudio extension in app/blocks/extensions/button.json instead.
 * The native Width panel (25/50/75/100) is hardcoded in the block's edit
 * component with no theme.json gate in WP 7.0, so that one is hidden via
 * CSS in editor-canvas.css when a button is selected.
 *
 * For `core/image`, clear Default/Rounded styles and shadow support -
 * radius is handled in theme CSS instead.
 *
 * @link https://developer.wordpress.org/reference/hooks/block_type_metadata/
 */
add_filter('block_type_metadata', function ($metadata) {
    $name = $metadata['name'] ?? null;

    if (in_array($name, ['core/heading', 'core/paragraph'], true)) {
        unset($metadata['supports']['typography']['fitText']);
    }

    if ($name === 'core/button') {
        $metadata['styles'] = [];

        foreach (['typography', 'shadow', 'color', 'spacing', '__experimentalBorder'] as $support) {
            unset($metadata['supports'][$support]);
        }
    }

    if ($name === 'core/image') {
        // Drop Default / Rounded style variants and the Shadow panel.
        // Border radius is owned by theme CSS (`.wp-block-image img`).
        $metadata['styles'] = [];
        unset($metadata['supports']['shadow']);
    }

    return $metadata;
});

/**
 * Inject scripts into the block editor.
 *
 * @return void
 */
add_action('admin_head', function () {
    if (! get_current_screen()?->is_block_editor()) {
        return;
    }

    if (! Vite::isRunningHot()) {
        $dependencies = json_decode(Vite::content('editor.deps.json'));

        foreach ($dependencies as $dependency) {
            if (! wp_script_is($dependency)) {
                wp_enqueue_script($dependency);
            }
        }
    }
    echo Vite::withEntryPoints([
        'resources/js/editor.js',
    ])->toHtml();
});

/**
 * Use the generated theme.json file.
 *
 * @return string
 */
add_filter('theme_file_path', function ($path, $file) {
    return $file === 'theme.json'
        ? public_path('build/assets/theme.json')
        : $path;
}, 10, 2);

/**
 * Disable on-demand block asset loading.
 *
 * @link https://core.trac.wordpress.org/ticket/61965
 */
add_filter('should_load_separate_core_block_assets', '__return_false');

/**
 * Register the initial theme setup.
 *
 * @return void
 */
add_action('after_setup_theme', function () {
    /**
     * Disable full-site editing support.
     *
     * @link https://wptavern.com/gutenberg-10-5-embeds-pdfs-adds-verse-block-color-options-and-introduces-new-patterns
     */
    remove_theme_support('block-templates');

    /**
     * Register the navigation menus.
     *
     * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
     */
    /*
     * Slugs match spa-headless-core and the Astro site: primary, footer,
     * legal, footer-social. Do not rename them.
     */
    register_nav_menus([
        'primary' => __('Primary', 'sage'),
        'footer' => __('Footer', 'sage'),
        'legal' => __('Legal', 'sage'),
        'footer-social' => __('Footer social', 'sage'),
    ]);

    /**
     * Disable the default block patterns.
     *
     * @link https://developer.wordpress.org/block-editor/developers/themes/theme-support/#disabling-the-default-block-patterns
     */
    remove_theme_support('core-block-patterns');

    /**
     * Enable plugins to manage the document title.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
     */
    add_theme_support('title-tag');

    /**
     * Enable post thumbnail support.
     *
     * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
     */
    add_theme_support('post-thumbnails');

    add_theme_support('custom-logo', [
        'height' => 80,
        'width' => 280,
        'flex-height' => true,
        'flex-width' => true,
    ]);

    add_theme_support('editor-styles');

    /**
     * Enable responsive embed support.
     *
     * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#responsive-embedded-content
     */
    add_theme_support('responsive-embeds');

    /**
     * Enable HTML5 markup support.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#html5
     */
    add_theme_support('html5', [
        'caption',
        'comment-form',
        'comment-list',
        'gallery',
        'search-form',
        'script',
        'style',
    ]);

    /**
     * Enable selective refresh for widgets in customizer.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#customize-selective-refresh-widgets
     */
    add_theme_support('customize-selective-refresh-widgets');
}, 20);

/**
 * Use the theme's NavWalker (Alpine dropdowns/off-canvas support) for every
 * wp_nav_menu() call, so it doesn't need to be passed manually each time -
 * see App\Walkers\NavWalker and App\Filters\Traits\navs.
 *
 * @param array $args
 * @return array
 */
add_filter('wp_nav_menu_args', function (array $args) {
    $args['walker'] = new \App\Walkers\NavWalker();

    return $args;
});

/**
 * Register the theme sidebars.
 *
 * @return void
 */
add_action('widgets_init', function () {
    $config = [
        'before_widget' => '<section class="widget %1$s %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h3>',
        'after_title' => '</h3>',
    ];

    register_sidebar([
        'name' => __('Primary', 'sage'),
        'id' => 'sidebar-primary',
    ] + $config);

    register_sidebar([
        'name' => __('Footer', 'sage'),
        'id' => 'sidebar-footer',
    ] + $config);
});
