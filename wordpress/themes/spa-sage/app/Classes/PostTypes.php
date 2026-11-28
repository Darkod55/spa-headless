<?php

namespace App;

if (! class_exists(__NAMESPACE__ . '\\PostTypes')) {
    /**
     * Custom post types and meta fields registered from the theme.
     * Prefer this over ACF when the site only needs one or two CPTs.
     *
     * @package App
     */
    class PostTypes
    {
        /**
         * Bump when rewrite args change so rules flush once on next request.
         */
        private const REWRITE_VERSION = 4;

        private static $instance;

        /**
         * Post type slug for podcasts.
         */
        public const PODCAST = 'podcast';

        /**
         * Post type slug for events.
         */
        public const EVENT = 'event';

        /**
         * Topics taxonomy slug (for podcasts).
         */
        public const TOPIC = 'topic';

        /**
         * Podcast meta keys.
         */
        public const META_PODCAST_NAME = 'podcast_name';
        public const META_PODCAST_LINK = 'podcast_link';
        public const META_HOST_QUOTE = 'podcast_host_quote';
        public const META_HOST = 'podcast_host';

        /**
         * Event meta keys.
         */
        public const META_EVENT_NAME = 'event_name';
        public const META_EVENT_HOST_ORG = 'event_host_organization';
        public const META_EVENT_DATE = 'event_date';
        public const META_EVENT_ATTENDANCE = 'event_attendance';
        public const META_EVENT_REGISTRATION_LINK = 'event_registration_link';

        /**
         * Podcast meta box / save nonce.
         */
        private const META_NONCE = 'mpx_podcast_meta_nonce';
        private const META_ACTION = 'mpx_save_podcast_meta';

        /**
         * Event meta box / save nonce.
         */
        private const EVENT_META_NONCE = 'mpx_event_meta_nonce';
        private const EVENT_META_ACTION = 'mpx_save_event_meta';

        /**
         * PostTypes constructor.
         */
        protected function __construct()
        {
            add_action('init', [$this, 'register']);
            add_action('after_switch_theme', [$this, 'flushRewrites']);
            add_action('add_meta_boxes', [$this, 'registerMetaBoxes']);
            add_action('add_meta_boxes', [$this, 'repositionTopicMetaBox'], 99);
            add_action('save_post_' . self::PODCAST, [$this, 'savePodcastMeta'], 10, 2);
            add_action('save_post_' . self::EVENT, [$this, 'saveEventMeta'], 10, 2);
            add_action('admin_enqueue_scripts', [$this, 'enqueueAdminStyles']);
        }

        /**
         * Register all theme CPTs, taxonomies, and post meta.
         *
         * @return void
         */
        public function register()
        {
            $this->registerPodcast();
            $this->registerEvent();
            $this->registerTopicTaxonomy();
            $this->registerPodcastMeta();
            $this->registerEventMeta();
            $this->maybeFlushRewrites();
        }

        /**
         * Flush rewrite rules after theme switch so /podcasts/ and /events/ resolve.
         *
         * @return void
         */
        public function flushRewrites()
        {
            $this->registerPodcast();
            $this->registerEvent();
            $this->registerTopicTaxonomy();
            flush_rewrite_rules();
            update_option('mpx_cpt_rewrite_version', self::REWRITE_VERSION, false);
        }

        /**
         * One-shot flush when CPT rewrite config is new or bumped.
         *
         * @return void
         */
        protected function maybeFlushRewrites()
        {
            $stored = (int) get_option('mpx_cpt_rewrite_version', 0);

            if ($stored === self::REWRITE_VERSION) {
                return;
            }

            flush_rewrite_rules(false);
            update_option('mpx_cpt_rewrite_version', self::REWRITE_VERSION, false);
        }

        /**
         * Podcast CPT — archive at /podcasts/.
         *
         * @return void
         */
        protected function registerPodcast()
        {
            $labels = [
                'name' => __('Podcasts', 'sage'),
                'singular_name' => __('Podcast', 'sage'),
                'add_new' => __('Add New', 'sage'),
                'add_new_item' => __('Add New Podcast', 'sage'),
                'edit_item' => __('Edit Podcast', 'sage'),
                'new_item' => __('New Podcast', 'sage'),
                'view_item' => __('View Podcast', 'sage'),
                'view_items' => __('View Podcasts', 'sage'),
                'search_items' => __('Search Podcasts', 'sage'),
                'not_found' => __('No podcasts found.', 'sage'),
                'not_found_in_trash' => __('No podcasts found in Trash.', 'sage'),
                'all_items' => __('All Podcasts', 'sage'),
                'archives' => __('Podcast Archives', 'sage'),
                'attributes' => __('Podcast Attributes', 'sage'),
                'insert_into_item' => __('Insert into podcast', 'sage'),
                'uploaded_to_this_item' => __('Uploaded to this podcast', 'sage'),
                'featured_image' => __('Featured image', 'sage'),
                'set_featured_image' => __('Set featured image', 'sage'),
                'remove_featured_image' => __('Remove featured image', 'sage'),
                'use_featured_image' => __('Use as featured image', 'sage'),
                'menu_name' => __('Podcasts', 'sage'),
                'filter_items_list' => __('Filter podcasts list', 'sage'),
                'items_list_navigation' => __('Podcasts list navigation', 'sage'),
                'items_list' => __('Podcasts list', 'sage'),
                'item_published' => __('Podcast published.', 'sage'),
                'item_published_privately' => __('Podcast published privately.', 'sage'),
                'item_reverted_to_draft' => __('Podcast reverted to draft.', 'sage'),
                'item_scheduled' => __('Podcast scheduled.', 'sage'),
                'item_updated' => __('Podcast updated.', 'sage'),
            ];

            register_post_type(self::PODCAST, [
                'labels' => $labels,
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_rest' => true,
                'menu_position' => 20,
                'menu_icon' => 'dashicons-microphone',
                'hierarchical' => false,
                'has_archive' => 'podcasts',
                'rewrite' => [
                    'slug' => 'podcast',
                    'with_front' => false,
                ],
                'supports' => [
                    'title',
                    'editor',
                    'thumbnail',
                    'excerpt',
                    'revisions',
                ],
                'exclude_from_search' => false,
                'publicly_queryable' => true,
            ]);
        }

        /**
         * Event CPT — archive at /events/, no block editor (meta box only).
         *
         * @return void
         */
        protected function registerEvent()
        {
            $labels = [
                'name' => __('Events', 'sage'),
                'singular_name' => __('Event', 'sage'),
                'add_new' => __('Add New', 'sage'),
                'add_new_item' => __('Add New Event', 'sage'),
                'edit_item' => __('Edit Event', 'sage'),
                'new_item' => __('New Event', 'sage'),
                'view_item' => __('View Event', 'sage'),
                'view_items' => __('View Events', 'sage'),
                'search_items' => __('Search Events', 'sage'),
                'not_found' => __('No events found.', 'sage'),
                'not_found_in_trash' => __('No events found in Trash.', 'sage'),
                'all_items' => __('All Events', 'sage'),
                'archives' => __('Event Archives', 'sage'),
                'attributes' => __('Event Attributes', 'sage'),
                'insert_into_item' => __('Insert into event', 'sage'),
                'uploaded_to_this_item' => __('Uploaded to this event', 'sage'),
                'featured_image' => __('Featured image', 'sage'),
                'set_featured_image' => __('Set featured image', 'sage'),
                'remove_featured_image' => __('Remove featured image', 'sage'),
                'use_featured_image' => __('Use as featured image', 'sage'),
                'menu_name' => __('Events', 'sage'),
                'filter_items_list' => __('Filter events list', 'sage'),
                'items_list_navigation' => __('Events list navigation', 'sage'),
                'items_list' => __('Events list', 'sage'),
                'item_published' => __('Event published.', 'sage'),
                'item_published_privately' => __('Event published privately.', 'sage'),
                'item_reverted_to_draft' => __('Event reverted to draft.', 'sage'),
                'item_scheduled' => __('Event scheduled.', 'sage'),
                'item_updated' => __('Event updated.', 'sage'),
            ];

            register_post_type(self::EVENT, [
                'labels' => $labels,
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_rest' => true,
                'menu_position' => 21,
                'menu_icon' => 'dashicons-calendar-alt',
                'hierarchical' => false,
                'has_archive' => false,
                'rewrite' => [
                    'slug' => 'event',
                    'with_front' => false,
                ],
                'supports' => [
                    'title',
                    'thumbnail',
                    'revisions',
                ],
                'exclude_from_search' => false,
                'publicly_queryable' => true,
            ]);
        }

        /**
         * Topics taxonomy for podcasts.
         *
         * @return void
         */
        protected function registerTopicTaxonomy()
        {
            $labels = [
                'name' => __('Topics', 'sage'),
                'singular_name' => __('Topic', 'sage'),
                'search_items' => __('Search Topics', 'sage'),
                'all_items' => __('All Topics', 'sage'),
                'parent_item' => __('Parent Topic', 'sage'),
                'parent_item_colon' => __('Parent Topic:', 'sage'),
                'edit_item' => __('Edit Topic', 'sage'),
                'view_item' => __('View Topic', 'sage'),
                'update_item' => __('Update Topic', 'sage'),
                'add_new_item' => __('Add New Topic', 'sage'),
                'new_item_name' => __('New Topic Name', 'sage'),
                'not_found' => __('No topics found.', 'sage'),
                'no_terms' => __('No topics', 'sage'),
                'filter_by_item' => __('Filter by topic', 'sage'),
                'items_list_navigation' => __('Topics list navigation', 'sage'),
                'items_list' => __('Topics list', 'sage'),
                'back_to_items' => __('← Go to Topics', 'sage'),
                'item_link' => __('Topic Link', 'sage'),
                'item_link_description' => __('A link to a topic', 'sage'),
                'menu_name' => __('Topics', 'sage'),
                'name_admin_bar' => __('Topic', 'sage'),
            ];

            register_taxonomy(self::TOPIC, [self::PODCAST], [
                'labels' => $labels,
                'public' => true,
                'hierarchical' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_nav_menus' => true,
                'show_in_rest' => true,
                'show_admin_column' => true,
                'show_tagcloud' => false,
                'rewrite' => [
                    'slug' => 'topic',
                    'with_front' => false,
                    'hierarchical' => true,
                ],
            ]);
        }

        /**
         * Place Topics under Featured image in the side column (classic meta boxes).
         * Block editor still lists Topics in the document sidebar via show_in_rest.
         *
         * @return void
         */
        public function repositionTopicMetaBox()
        {
            $screen = function_exists('get_current_screen') ? get_current_screen() : null;

            if (! $screen || $screen->post_type !== self::PODCAST) {
                return;
            }

            remove_meta_box(self::TOPIC . 'div', self::PODCAST, 'side');

            add_meta_box(
                self::TOPIC . 'div',
                __('Topics', 'sage'),
                'post_categories_meta_box',
                self::PODCAST,
                'side',
                'low',
                ['taxonomy' => self::TOPIC]
            );
        }

        /**
         * Field definitions for the podcast meta box.
         *
         * @return array<int, array{key: string, label: string, type: string, sanitize: string}>
         */
        protected function podcastFields(): array
        {
            return [
                [
                    'key' => self::META_PODCAST_NAME,
                    'label' => __('Podcast Name', 'sage'),
                    'type' => 'text',
                    'sanitize' => 'text',
                ],
                [
                    'key' => self::META_PODCAST_LINK,
                    'label' => __('Podcast Link', 'sage'),
                    'type' => 'url',
                    'sanitize' => 'url',
                ],
                [
                    'key' => self::META_HOST_QUOTE,
                    'label' => __('Podcast Host Quote', 'sage'),
                    'type' => 'text',
                    'sanitize' => 'text',
                ],
                [
                    'key' => self::META_HOST,
                    'label' => __('Host', 'sage'),
                    'type' => 'text',
                    'sanitize' => 'text',
                ],
            ];
        }

        /**
         * Field definitions for the event meta box.
         *
         * @return array<int, array{key: string, label: string, type: string, sanitize: string, options?: array<string, string>}>
         */
        protected function eventFields(): array
        {
            return [
                [
                    'key' => self::META_EVENT_NAME,
                    'label' => __('Event Name', 'sage'),
                    'type' => 'text',
                    'sanitize' => 'text',
                ],
                [
                    'key' => self::META_EVENT_HOST_ORG,
                    'label' => __('Host Organization', 'sage'),
                    'type' => 'text',
                    'sanitize' => 'text',
                ],
                [
                    'key' => self::META_EVENT_DATE,
                    'label' => __('Date', 'sage'),
                    'type' => 'date',
                    'sanitize' => 'date',
                ],
                [
                    'key' => self::META_EVENT_ATTENDANCE,
                    'label' => __('Online / In Person', 'sage'),
                    'type' => 'radio',
                    'sanitize' => 'radio',
                    'options' => [
                        'online' => __('Online', 'sage'),
                        'person' => __('In Person', 'sage'),
                    ],
                ],
                [
                    'key' => self::META_EVENT_REGISTRATION_LINK,
                    'label' => __('External Registration Link', 'sage'),
                    'type' => 'url',
                    'sanitize' => 'url',
                ],
            ];
        }

        /**
         * Register podcast meta for REST / future block use.
         *
         * @return void
         */
        protected function registerPodcastMeta()
        {
            foreach ($this->podcastFields() as $field) {
                register_post_meta(self::PODCAST, $field['key'], [
                    'type' => 'string',
                    'single' => true,
                    'show_in_rest' => true,
                    'auth_callback' => static function () {
                        return current_user_can('edit_posts');
                    },
                ]);
            }
        }

        /**
         * Register event meta for REST / future template use.
         *
         * @return void
         */
        protected function registerEventMeta()
        {
            foreach ($this->eventFields() as $field) {
                register_post_meta(self::EVENT, $field['key'], [
                    'type' => 'string',
                    'single' => true,
                    'show_in_rest' => true,
                    'auth_callback' => static function () {
                        return current_user_can('edit_posts');
                    },
                ]);
            }
        }

        /**
         * Meta boxes on podcast / event edit screens.
         *
         * @return void
         */
        public function registerMetaBoxes()
        {
            add_meta_box(
                'mpx-podcast-details',
                __('Podcast Details', 'sage'),
                [$this, 'renderPodcastMetaBox'],
                self::PODCAST,
                'normal',
                'high'
            );

            add_meta_box(
                'mpx-event-details',
                __('Event Details', 'sage'),
                [$this, 'renderEventMetaBox'],
                self::EVENT,
                'normal',
                'high'
            );
        }

        /**
         * Render podcast details fields.
         *
         * @param \WP_Post $post
         * @return void
         */
        public function renderPodcastMetaBox($post)
        {
            wp_nonce_field(self::META_ACTION, self::META_NONCE);

            echo '<div class="mpx-cpt-meta">';
            $this->renderMetaFields($post->ID, $this->podcastFields());
            echo '</div>';
        }

        /**
         * Render event details fields.
         *
         * @param \WP_Post $post
         * @return void
         */
        public function renderEventMetaBox($post)
        {
            wp_nonce_field(self::EVENT_META_ACTION, self::EVENT_META_NONCE);

            echo '<div class="mpx-cpt-meta">';
            $this->renderMetaFields($post->ID, $this->eventFields());
            echo '</div>';
        }

        /**
         * Render a list of meta fields for a post.
         *
         * @param int   $postId
         * @param array $fields
         * @return void
         */
        protected function renderMetaFields(int $postId, array $fields): void
        {
            foreach ($fields as $field) {
                $value = get_post_meta($postId, $field['key'], true);
                $value = is_string($value) ? $value : '';
                $id = 'mpx_' . $field['key'];
                $type = $field['type'] ?? 'text';

                echo '<div class="mpx-cpt-meta__field">';

                if ($type === 'radio') {
                    printf(
                        '<span class="mpx-cpt-meta__legend"><strong>%s</strong></span>',
                        esc_html($field['label'])
                    );
                    echo '<div class="mpx-cpt-meta__radios" role="radiogroup" aria-label="' . esc_attr($field['label']) . '">';

                    foreach ($field['options'] ?? [] as $optionValue => $optionLabel) {
                        $optionId = $id . '_' . $optionValue;
                        printf(
                            '<label class="mpx-cpt-meta__radio" for="%1$s"><input type="radio" id="%1$s" name="%2$s" value="%3$s"%4$s /> %5$s</label>',
                            esc_attr($optionId),
                            esc_attr($field['key']),
                            esc_attr($optionValue),
                            checked($value, $optionValue, false),
                            esc_html($optionLabel)
                        );
                    }

                    echo '</div>';
                } else {
                    $inputType = match ($type) {
                        'url' => 'url',
                        'date' => 'date',
                        default => 'text',
                    };

                    printf(
                        '<label for="%1$s"><strong>%2$s</strong></label><input type="%3$s" class="widefat" id="%1$s" name="%4$s" value="%5$s"%6$s />',
                        esc_attr($id),
                        esc_html($field['label']),
                        esc_attr($inputType),
                        esc_attr($field['key']),
                        esc_attr($value),
                        $inputType === 'url' ? ' placeholder="https://"' : ''
                    );
                }

                echo '</div>';
            }
        }

        /**
         * Persist podcast meta on save.
         *
         * @param int      $postId
         * @param \WP_Post $post
         * @return void
         */
        public function savePodcastMeta($postId, $post)
        {
            $this->saveMetaFields(
                $postId,
                $post,
                self::PODCAST,
                self::META_NONCE,
                self::META_ACTION,
                $this->podcastFields()
            );
        }

        /**
         * Persist event meta on save.
         *
         * @param int      $postId
         * @param \WP_Post $post
         * @return void
         */
        public function saveEventMeta($postId, $post)
        {
            $this->saveMetaFields(
                $postId,
                $post,
                self::EVENT,
                self::EVENT_META_NONCE,
                self::EVENT_META_ACTION,
                $this->eventFields()
            );
        }

        /**
         * Shared meta save for podcast / event fields.
         *
         * @param int      $postId
         * @param \WP_Post $post
         * @param string   $postType
         * @param string   $nonceKey
         * @param string   $nonceAction
         * @param array    $fields
         * @return void
         */
        protected function saveMetaFields($postId, $post, string $postType, string $nonceKey, string $nonceAction, array $fields): void
        {
            if (! isset($_POST[$nonceKey])
                || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$nonceKey])), $nonceAction)
            ) {
                return;
            }

            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            if ($post->post_type !== $postType) {
                return;
            }

            if (! current_user_can('edit_post', $postId)) {
                return;
            }

            foreach ($fields as $field) {
                $raw = $_POST[$field['key']] ?? '';
                $raw = is_string($raw) ? wp_unslash($raw) : '';
                $value = $this->sanitizeMetaValue($raw, $field);

                if ($value === '') {
                    delete_post_meta($postId, $field['key']);
                } else {
                    update_post_meta($postId, $field['key'], $value);
                }
            }
        }

        /**
         * Sanitize a submitted meta value by field config.
         *
         * @param string $raw
         * @param array  $field
         * @return string
         */
        protected function sanitizeMetaValue(string $raw, array $field): string
        {
            $sanitize = $field['sanitize'] ?? 'text';

            return match ($sanitize) {
                'url' => esc_url_raw(trim($raw)),
                'date' => $this->sanitizeDate($raw),
                'radio' => $this->sanitizeRadio($raw, array_keys($field['options'] ?? [])),
                default => sanitize_text_field($raw),
            };
        }

        /**
         * Keep Y-m-d dates only.
         *
         * @param string $raw
         * @return string
         */
        protected function sanitizeDate(string $raw): string
        {
            $raw = trim($raw);

            if ($raw === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
                return '';
            }

            $parts = array_map('intval', explode('-', $raw));

            if (! checkdate($parts[1], $parts[2], $parts[0])) {
                return '';
            }

            return $raw;
        }

        /**
         * Keep radio value only when it matches an allowed option.
         *
         * @param string   $raw
         * @param string[] $allowed
         * @return string
         */
        protected function sanitizeRadio(string $raw, array $allowed): string
        {
            $raw = sanitize_text_field($raw);

            return in_array($raw, $allowed, true) ? $raw : '';
        }

        /**
         * Light admin styles for CPT meta boxes.
         *
         * @param string $hook
         * @return void
         */
        public function enqueueAdminStyles($hook)
        {
            if (! in_array($hook, ['post.php', 'post-new.php'], true)) {
                return;
            }

            $screen = function_exists('get_current_screen') ? get_current_screen() : null;

            if (! $screen || ! in_array($screen->post_type, [self::PODCAST, self::EVENT], true)) {
                return;
            }

            wp_register_style('mpx-cpt-admin', false, [], null);
            wp_enqueue_style('mpx-cpt-admin');
            wp_add_inline_style(
                'mpx-cpt-admin',
                '.mpx-cpt-meta,.mpx-podcast-meta{display:grid;gap:12px;margin:0}'
                . '.mpx-cpt-meta__field,.mpx-podcast-meta__field{margin:0}'
                . '.mpx-cpt-meta__field label,.mpx-podcast-meta__field label,.mpx-cpt-meta__legend{display:block;margin-bottom:4px}'
                . '.mpx-cpt-meta__radios{display:flex;flex-wrap:wrap;gap:12px 20px}'
                . '.mpx-cpt-meta__radio{display:inline-flex;align-items:center;gap:6px;margin:0;font-weight:400}'
            );
        }

        /**
         * Read a podcast meta value (empty string when unset).
         *
         * @param int    $postId
         * @param string $key
         * @return string
         */
        public static function getPodcastMeta(int $postId, string $key): string
        {
            return self::getMeta($postId, $key);
        }

        /**
         * Read an event meta value (empty string when unset).
         *
         * @param int    $postId
         * @param string $key
         * @return string
         */
        public static function getEventMeta(int $postId, string $key): string
        {
            return self::getMeta($postId, $key);
        }

        /**
         * Query past or upcoming events by event_date meta.
         *
         * Upcoming: date >= today, ascending (soonest first).
         * Past: date < today, descending (most recent first).
         *
         * @param string $scope  'upcoming' | 'past'
         * @param int    $limit  posts_per_page (-1 = all)
         * @return \WP_Query
         */
        public static function queryEvents(string $scope = 'upcoming', int $limit = -1): \WP_Query
        {
            $scope = $scope === 'past' ? 'past' : 'upcoming';
            $today = current_time('Y-m-d');

            return new \WP_Query([
                'post_type' => self::EVENT,
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'ignore_sticky_posts' => true,
                'meta_key' => self::META_EVENT_DATE,
                'orderby' => 'meta_value',
                'order' => $scope === 'past' ? 'DESC' : 'ASC',
                'meta_query' => [
                    [
                        'key' => self::META_EVENT_DATE,
                        'value' => $today,
                        'compare' => $scope === 'past' ? '<' : '>=',
                        'type' => 'DATE',
                    ],
                ],
            ]);
        }

        /**
         * Human label for event_attendance meta.
         *
         * @param string $value  online|person
         * @return string
         */
        public static function eventAttendanceLabel(string $value): string
        {
            return match ($value) {
                'online' => __('Online', 'sage'),
                'person' => __('In Person', 'sage'),
                default => '',
            };
        }

        /**
         * Read a post meta string (empty when unset).
         *
         * @param int    $postId
         * @param string $key
         * @return string
         */
        public static function getMeta(int $postId, string $key): string
        {
            $value = get_post_meta($postId, $key, true);

            return is_string($value) ? $value : '';
        }

        /**
         * Singleton.
         *
         * @return self
         */
        public static function instance()
        {
            if (! (self::$instance instanceof self)) {
                self::$instance = new self;
            }

            return self::$instance;
        }
    }

    PostTypes::instance();
}
