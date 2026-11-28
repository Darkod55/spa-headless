<?php

namespace App;

if (! class_exists(__NAMESPACE__ . '\\WebsiteModules')) {
    /**
     * Private Website Modules library page.
     *
     * Auto-wraps each top-level block with a Copy control that puts
     * Gutenberg-serialized markup on the clipboard.
     */
    class WebsiteModules
    {
        private static $instance;

        public const TEMPLATE = 'tpl-website-modules.blade.php';

        protected function __construct()
        {
            add_action('template_redirect', [$this, 'restrictAccess']);
            add_filter('wp_robots', [$this, 'noindex']);
            add_filter('rank_math/frontend/robots', [$this, 'rankMathNoindex']);
        }

        /**
         * Whether the current request is the Website Modules page.
         */
        public static function isModulesPage(): bool
        {
            return is_page_template(self::TEMPLATE);
        }

        /**
         * Editors only — send others to login (then back here).
         */
        public function restrictAccess(): void
        {
            if (! self::isModulesPage()) {
                return;
            }

            if (is_user_logged_in() && current_user_can('edit_posts')) {
                return;
            }

            auth_redirect();
        }

        /**
         * Core robots API — noindex / nofollow.
         *
         * @param array<string, bool|string> $robots
         * @return array<string, bool|string>
         */
        public function noindex(array $robots): array
        {
            if (! self::isModulesPage()) {
                return $robots;
            }

            $robots['noindex'] = true;
            $robots['nofollow'] = true;

            return $robots;
        }

        /**
         * Rank Math robots override when the plugin is active.
         *
         * @param array<string, string> $robots
         * @return array<string, string>
         */
        public function rankMathNoindex(array $robots): array
        {
            if (! self::isModulesPage()) {
                return $robots;
            }

            $robots['index'] = 'noindex';
            $robots['follow'] = 'nofollow';

            return $robots;
        }

        /**
         * Top-level blocks from the current page, ready to preview + copy.
         *
         * @return array<int, array{name: string, title: string, html: string, serialized: string}>
         */
        public static function modules(): array
        {
            $post = get_post();
            if (! $post instanceof \WP_Post) {
                return [];
            }

            $modules = [];
            $registry = \WP_Block_Type_Registry::get_instance();

            foreach (parse_blocks($post->post_content) as $block) {
                if (empty($block['blockName'])) {
                    continue;
                }

                $name = (string) $block['blockName'];
                $type = $registry->get_registered($name);
                $title = $type && ! empty($type->title) ? (string) $type->title : $name;
                $serialized = serialize_block($block);

                if (trim($serialized) === '') {
                    continue;
                }

                $modules[] = [
                    'name' => $name,
                    'title' => $title,
                    'html' => render_block($block),
                    'serialized' => $serialized,
                ];
            }

            return $modules;
        }

        public static function instance(): self
        {
            if (! (self::$instance instanceof self)) {
                self::$instance = new self;
            }

            return self::$instance;
        }
    }

    WebsiteModules::instance();
}
