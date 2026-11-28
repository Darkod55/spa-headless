<?php

// IMPORTANT: leave as base namespace for Blade Directives
namespace App;

if (!class_exists('App\SetupPlus')) {
    /**
     * Class SetupPlus
     * @since ITD Web v1.1
     * @package App
     */
    class SetupPlus
    {
        private static $instance;

        /**
         * SetupPlus constructor.
         */
        protected function __construct()
        {
            // Prefer the combined library handle so dequeue can remove it in one shot.
            // (Per-block wp-block-* handles bypass the old wp-block-library dequeue.)
            add_filter('should_load_separate_core_block_assets', '__return_false', 100);
            add_filter('should_load_block_assets_on_demand', '__return_false', 100);

            remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
            remove_action('wp_footer', 'wp_enqueue_global_styles', 1);

            add_action('wp_enqueue_scripts', [$this, 'dequeueBlockEditorAssets'], 100);
            add_action('wp_print_styles', [$this, 'dequeueBlockEditorAssets'], 100);
            add_filter('wp_revisions_to_keep', [$this, 'revisions'], 10, 2);
        }

        /**
         * Strip WordPress core block / global-styles CSS from the front end.
         *
         * Theme CSS (Vite + Blockstudio Tailwind) owns layout and look; core
         * paragraph/heading/button/image/gallery/embed + theme.json preset
         * dumps are not needed. Also clears per-block handles in case separate
         * assets are re-enabled elsewhere.
         *
         * @return void
         */
        public function dequeueBlockEditorAssets()
        {
            foreach ([
                'wp-block-library',
                'wp-block-library-theme',
                'classic-theme-styles',
                'global-styles',
                'wp-img-auto-sizes-contain',
                'editor-buttons',
                'buttons',
            ] as $handle) {
                wp_dequeue_style($handle);
            }

            $wp_styles = wp_styles();

            foreach (array_keys($wp_styles->registered) as $handle) {
                if (str_starts_with((string) $handle, 'wp-block-')) {
                    wp_dequeue_style($handle);
                }
            }
        }

        /**
         * Set revisions per post type
         *
         * @param $num
         * @param $post
         * @return int
         */
        public function revisions($num, $post)
        {
            if (in_array($post->post_type, ['post', 'page'], true)) {
                $num = 3;
            }

            return $num;
        }

        /**
         * Singleton
         *
         * @return SetupPlus
         */
        public static function instance()
        {
            if (! (self::$instance instanceof self)) {
                self::$instance = new self;
            }

            return self::$instance;
        }
    }

    SetupPlus::instance();
}
