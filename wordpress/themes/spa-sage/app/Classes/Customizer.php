<?php

namespace App\Customizer;

if (!class_exists(__NAMESPACE__.'\Customizer')) {
    /**
     * Class Customizer
     * @since ITD Web v1.1
     * @package App\Customizer
     */
    class Customizer
    {
        private static $instance;

        // the site logo's primary class
        protected $site_logo_container_class = '.navbar-brand-link';
        protected $site_logo_class = '.navbar-brand img';

        /**
         * Customizer constructor.
         */
        protected function __construct() {
            // add Customizer settings
            add_action('customize_register', [$this, 'themeCustomizer']);
            // add Customizer styles
            add_action('customize_controls_print_styles', [$this, 'customizerControlsPrintStyles'], 999);
        }

        /**
         * themeCustomizer
         *
         * @link https://codex.wordpress.org/Class_Reference/WP_Customize_Manager
         * Also see app/admin.php
         *
         * @param \WP_Customize_Manager $wp_customize
         */
        public function themeCustomizer($wp_customize)
        {
            // Controls site logo
            $this->customizerSiteLogo($wp_customize);

            // Controls for alternative site logo
            $this->customizerSiteLogoAlternative($wp_customize);
        }

        /**
         * Custom Logo
         *
         * Upload and resize main site logo in Customizer
         * @param \WP_Customize_Manager $wp_customize
         */
        private function customizerSiteLogo($wp_customize)
        {
            // add selective refresh
            $wp_customize->selective_refresh->add_partial( 'custom_logo', ['selector' => $this->site_logo_container_class]);

            // add new 'Site Identity' section (in 'title_tagline' panel)
            $wp_customize->add_section('title_tagline',
                [
                    'title'           => __('Site Identity'),
                    'priority'        => 0,
                    'description'     => __('<b>Optionally</b> upload custom logos. Note: these will replace the defaults loaded by the theme'),
                ]
            );

            /**
             * Custom logo resizing
             */
            // add setting
            $wp_customize->add_setting('logo_width', [
                'default' => '150',
                'type' => 'theme_mod',
                'capability' => 'edit_theme_options',
                'transport' => 'postMessage',
                'sanitize_callback' => [$this, 'sanitiseLogoWidth'],
            ]);

            // add control
            $wp_customize->add_control('logo_width', [
                'type' => 'text',
                'section' => 'title_tagline',
                'label' => __('Logo width', 'sage'),
                'input_attrs' => [
                    'class' => 'customise-logo-width',
                    'style' => 'width: 100%;',
                    'placeholder' => 'e.g. 225',
                ],
                'description' => __(
                    'Header logo max width in pixels (e.g. <code>225</code>). Scales fluidly from two-thirds of this value at <code>767px</code> viewports up to the full width at <code>1300px</code> (e.g. <code>225</code> → <code>150px</code>–<code>225px</code>).',
                    'sage'
                ),
                'priority' => 8,
            ]);

            // add partial refresh
            $wp_customize->selective_refresh->add_partial( 'logo_width', ['selector' => $this->site_logo_class]);
        }

        /**
         * Custom Alternative Logo
         *
         * A secondary logo for use cases where a inverted  logo is required
         * @param \WP_Customize_Manager $wp_customize
         */
        private function customizerSiteLogoAlternative($wp_customize){
            // add selective refresh
            $wp_customize->selective_refresh->add_partial('spa_alt_logo', ['selector' => $this->site_logo_container_class]);

            $wp_customize->add_setting('spa_alt_logo', [
                'default' => 0,
                'type' => 'theme_mod',
                'capability' => 'edit_theme_options',
                'transport' => 'refresh',
                'sanitize_callback' => 'absint',
            ]);

            $wp_customize->add_control(
                new \WP_Customize_Media_Control(
                    $wp_customize,
                    'spa_alt_logo',
                    [
                        'label' => __('Alternative logo', 'sage'),
                        'description' => __('A second wordmark, for example a white logo on the navy footer.', 'sage'),
                        'section' => 'title_tagline',
                        'mime_type' => 'image',
                        'priority' => 9,
                        'button_labels' => [
                            'select' => __('Select logo', 'sage'),
                            'change' => __('Change logo', 'sage'),
                            'remove' => __('Remove', 'sage'),
                            'default' => __('Default', 'sage'),
                            'placeholder' => __('No logo selected', 'sage'),
                            'frame_title' => __('Select logo', 'sage'),
                            'frame_button' => __('Choose logo', 'sage'),
                        ],
                    ]
                )
            );

            $wp_customize->add_setting('spa_alt_logo_in_footer', [
                'default' => false,
                'type' => 'theme_mod',
                'capability' => 'edit_theme_options',
                'sanitize_callback' => static function ($value): bool {
                    return rest_sanitize_boolean($value);
                },
            ]);
            $wp_customize->add_control('spa_alt_logo_in_footer', [
                'label' => __('Show in footer', 'sage'),
                'section' => 'title_tagline',
                'type' => 'checkbox',
                'priority' => 10,
                'active_callback' => static function (): bool {
                    return (int) get_theme_mod('spa_alt_logo', 0) > 0;
                },
            ]);
        }

        /**
         * Sanitise the logo width setting.
         *
         * Accepts either a plain number (treated as a pixel value by the
         * consumer, see App\Helpers\Traits\media::getSiteLogo()) or a CSS
         * length/clamp() expression, so the logo can resize fluidly.
         *
         * Since this is echoed straight into an inline `style` attribute,
         * strip anything that isn't a character a CSS length/clamp() value
         * could legitimately need, to rule out breaking out of the
         * attribute rather than trying to fully validate CSS syntax.
         *
         * @param string $value
         * @param \WP_Customize_Setting $setting
         * @return string      - if valid, the sanitised value, if empty the default value
         */
        public function sanitiseLogoWidth($value, $setting)
        {
            $value = trim((string) preg_replace('/[^0-9a-zA-Z%.\-+*\/(),\s]/', '', (string) $value));

            return ($value !== '') ? $value : $setting->default;
        }

        /**
         * Add styles to Customizer
         */
        public function customizerControlsPrintStyles()
        {
            ?>
            <style>
                #customize-control-custom_logo img,
                #customize-control-alt_logo img {
                    max-height: 100px;
                }
            </style>
            <?php
        }

        /**
         * Singleton
         *
         * @return Customizer
         */
        public static function instance()
        {
            if (! (self::$instance instanceof self)) {
                self::$instance = new self;
            }

            return self::$instance;
        }
    }

    Customizer::instance();
}
