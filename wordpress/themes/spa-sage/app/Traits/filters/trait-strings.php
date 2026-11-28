<?php

namespace App\Filters\Traits;

/**
 * Trait strings
 *
 * @package App\Filters\Traits
 */
trait strings
{
    /**
     * Filters:
     *  'the_content_lite'
     */
    public function coreContentFilter()
    {
        /**
         * Filter the content with only CORE filters
         *
         * Thanks to Bill!
         * @link: https://gist.githubusercontent.com/billerickson/cd77f7b226b9379f9db75c38b0f4b3d5/raw/e32d56373e0f16544063a3e625d88b00941c1afd/functions.php
         *
         * @param $the_content
         * @return array
         */
        global $wp_embed;
        add_filter('the_content_lite', [$wp_embed, 'run_shortcode'], 8);
        add_filter('the_content_lite', [$wp_embed, 'autoembed'],     8);
        add_filter('the_content_lite', 'wptexturize'      );
        add_filter('the_content_lite', 'convert_chars'    );
        add_filter('the_content_lite', 'wpautop'          );
        add_filter('the_content_lite', 'shortcode_unautop');
        add_filter('the_content_lite', 'do_shortcode'     );
    }

    /**
     * Disabling comments and pingbacks
     */
    public function disableCommentsAndPingbacks()
    {
        add_action('admin_init', function () {
            global $pagenow;

            if ($pagenow === 'edit-comments.php') {
                wp_safe_redirect(admin_url());
                exit;
            }

            foreach (get_post_types() as $post_type) {
                if (post_type_supports($post_type, 'comments')) {
                    remove_post_type_support($post_type, 'comments');
                    remove_post_type_support($post_type, 'trackbacks');
                }
            }
        });

        // The 'dashboard_recent_comments' widget is only registered once wp_dashboard_setup
        // runs, so it must be removed there rather than on the earlier admin_init hook.
        add_action('wp_dashboard_setup', function () {
            remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
        });

        add_filter('comments_open', '__return_false', 20, 2);
        add_filter('pings_open', '__return_false', 20, 2);
        add_filter('comments_array', '__return_empty_array', 10, 2);

        add_action('admin_menu', function () {
            remove_menu_page('edit-comments.php');
        });

        add_action('admin_bar_menu', function ($wp_admin_bar) {
            $wp_admin_bar->remove_node('comments');
        }, 999);
    }
}
