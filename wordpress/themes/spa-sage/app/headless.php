<?php

/**
 * Headless theme mods the spa-headless-core plugin already reads.
 *
 * Menu locations are registered in app/setup.php as primary, footer,
 * legal, and footer-social. Alternative logo settings live in
 * App\Customizer. This file adds the announcement bar, SVG uploads,
 * and the Customizer script that shows "Show in footer" live.
 */

add_action('customize_register', 'spa_sage_customize_announcement');
add_action('customize_controls_enqueue_scripts', 'spa_sage_customize_controls');
add_filter('upload_mimes', 'spa_sage_allow_svg_mime');
add_filter('wp_check_filetype_and_ext', 'spa_sage_fix_svg_mime', 10, 4);

/**
 * Announcement bar. Astro reads these mods from GET /wp-json/spa/v1/site.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function spa_sage_customize_announcement(WP_Customize_Manager $wp_customize): void
{
    $defaults = function_exists('spa_headless_announcement_defaults')
        ? spa_headless_announcement_defaults()
        : [
            'enabled' => true,
            'message' => "Is your website underperforming?\nNot ranking on Google or showing up in AI search?",
            'linkLabel' => 'Book your free website audit',
            'url' => '/contact?enquiry=website-audit',
            'target' => '',
        ];

    $wp_customize->add_section('spa_announcement', [
        'title' => __('Announcement bar', 'sage'),
        'priority' => 30,
    ]);

    $wp_customize->add_setting('spa_announcement_enabled', [
        'default' => $defaults['enabled'],
        'sanitize_callback' => static function ($value): bool {
            return rest_sanitize_boolean($value);
        },
    ]);
    $wp_customize->add_control('spa_announcement_enabled', [
        'section' => 'spa_announcement',
        'label' => __('Show the announcement bar', 'sage'),
        'type' => 'checkbox',
    ]);

    $wp_customize->add_setting('spa_announcement_message', [
        'default' => $defaults['message'],
        'sanitize_callback' => 'sanitize_textarea_field',
    ]);
    $wp_customize->add_control('spa_announcement_message', [
        'section' => 'spa_announcement',
        'label' => __('Message', 'sage'),
        'description' => __('A line break is hidden on desktop and shown on small screens.', 'sage'),
        'type' => 'textarea',
    ]);

    $wp_customize->add_setting('spa_announcement_link_label', [
        'default' => $defaults['linkLabel'],
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('spa_announcement_link_label', [
        'section' => 'spa_announcement',
        'label' => __('Link label', 'sage'),
        'type' => 'text',
    ]);

    $wp_customize->add_setting('spa_announcement_link_url', [
        'default' => $defaults['url'],
        'sanitize_callback' => static function ($value): string {
            return esc_url_raw(is_string($value) ? trim($value) : '');
        },
    ]);
    $wp_customize->add_control('spa_announcement_link_url', [
        'section' => 'spa_announcement',
        'label' => __('Link URL', 'sage'),
        'description' => __('A path such as /contact?enquiry=website-audit, or a full URL.', 'sage'),
        'type' => 'text',
    ]);

    $wp_customize->add_setting('spa_announcement_link_target', [
        'default' => $defaults['target'],
        'sanitize_callback' => static function ($value): string {
            return '_blank' === $value ? '_blank' : '';
        },
    ]);
    $wp_customize->add_control('spa_announcement_link_target', [
        'section' => 'spa_announcement',
        'label' => __('Open link in', 'sage'),
        'type' => 'select',
        'choices' => [
            '' => __('Same tab', 'sage'),
            '_blank' => __('A new tab', 'sage'),
        ],
    ]);
}

/**
 * Reveal "Show in footer" as soon as an alternative logo is chosen.
 */
function spa_sage_customize_controls(): void
{
    wp_enqueue_script(
        'spa-customize-controls',
        get_theme_file_uri('resources/js/customize-controls.js'),
        ['customize-controls'],
        wp_get_theme()->get('Version'),
        true
    );
}

/**
 * @param array<string, string> $mimes
 * @return array<string, string>
 */
function spa_sage_allow_svg_mime(array $mimes): array
{
    $mimes['svg'] = 'image/svg+xml';

    return $mimes;
}

/**
 * @param array<string, string|false> $data
 * @return array<string, string|false>
 */
function spa_sage_fix_svg_mime(array $data, string $file, string $filename, $mimes): array
{
    unset($file, $mimes);

    if ('svg' !== strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
        return $data;
    }

    $data['ext'] = 'svg';
    $data['type'] = 'image/svg+xml';

    return $data;
}
