<?php

namespace App\Filters\Traits;

use function App\helpers;

/**
 * Trait navs
 *
 * @package App\Filters\Traits
 *
 * Filters:
 *  'nav_menu_item_id' (disabled, see navFilters())
 *  'nav_menu_css_class'
 *  'nav_menu_item_title'
 *  'nav_menu_submenu_css_class' (consumed by App\Walkers\NavWalker::start_lvl())
 *  'nav_menu_link_attributes'
 *
 * Dropdown open/close state (`x-data`) lives in App\Walkers\NavWalker, which
 * is applied to every wp_nav_menu() call automatically via the
 * 'wp_nav_menu_args' filter in app/setup.php.
 */
trait navs
{
    public function navFilters()
    {
        /**
         * Drop the id="menu-item-24" attribute WordPress prints on every
         * <li> - it's never targeted by anything here, and is part of the
         * same default noise cleaned up in the 'nav_menu_css_class' filter
         * below.
         */
        add_filter('nav_menu_item_id', '__return_null');

        /**
         * Clean up WordPress's default per-item <li> class noise, e.g.
         *
         *   menu-item menu-item-type-post_type menu-item-object-page
         *   menu-item-home current-menu-item page_item page-item-8
         *   current_page_item menu-item-24
         *
         * becomes:
         *
         *   menu-item active menu-home
         *
         * roots/soil did this via its own NavWalker, but it's abandoned
         * and its replacement here, roots/acorn-prettify, doesn't cover
         * nav menus - so it's ported by hand instead.
         *
         * Also adds a positioning context to items with a dropdown, and
         * drops the resulting 'active' class again from anchors that
         * point to a homepage fragment link (e.g. '/#pricing'), so it
         * isn't stuck "active" on every page.
         *
         * @param array $classes
         * @param \WP_Post $item
         * @return array
         */
        add_filter('nav_menu_css_class', function ($classes, $item) {
            if (in_array('menu-item-has-children', $classes, true)) {
                $classes[] = 'relative';
            }

            // Collapse core's current-item/-parent/-ancestor classes into one 'active' class.
            $classes = preg_replace('/current[-_](menu|page)[-_](item|parent|ancestor)/', 'active', $classes);

            if (in_array('active', $classes, true) && str_contains($item->url, '/#')) {
                $classes = array_diff($classes, ['active']);
            }

            /**
             * Strip the rest of core's noise: menu-item-type-*,
             * menu-item-object-*, the numeric menu-item-{ID}, menu-item-home,
             * and the page_item / page-item-{ID} pair core adds to every
             * page-type item. Editor-added custom classes (e.g.
             * 'menu-btn-*', handled in 'nav_menu_link_attributes' below via
             * $item->classes rather than this filtered $classes array)
             * don't match any of these and are left untouched.
             */
            $classes = preg_replace([
                '/^menu-item-type-\w+$/',
                '/^menu-item-object-\w+$/',
                '/^menu-item-\d+$/',
                '/^menu-item-home$/',
                '/^page[-_]item(-\d+)?$/',
            ], '', $classes);

            $classes[] = 'menu-' . sanitize_title($item->title);

            return array_values(array_filter(array_unique($classes)));
        }, 100, 2);

        /**
         * Tailwind classes for the submenu wrapper.
         *
         * Consumed by App\Walkers\NavWalker::start_lvl(), not printed directly
         * by WordPress core (core's default walker doesn't call this filter
         * with enough control over the rest of the <ul> tag).
         *
         * Pass a truthy `offcanvas` key in wp_nav_menu()'s $args (see
         * resources/views/sections/header.blade.php) to get a stacked
         * accordion-style submenu instead of the desktop flyout - both
         * share the same open/close state via NavWalker's `x-data`.
         *
         * The desktop flyout's actual look (background/text colour,
         * rounding, shadow, item padding) lives in the '.nav-dropdown'
         * component class (see resources/css/theme/nav.css) rather than
         * inline utilities here, so it's a one-file edit when reusing
         * this theme on a project with different brand colours.
         *
         * @param array $classes
         * @param object $args
         * @param int $depth
         * @return array
         */
        add_filter('nav_menu_submenu_css_class', function ($classes, $args, $depth) {
            if (! empty($args->offcanvas)) {
                $classes[] = 'ml-4 flex flex-col gap-1';

                return $classes;
            }

            $classes[] = 'nav-dropdown';

            return $classes;
        }, 10, 3);

        /**
         * Tailwind + Alpine attributes for menu item links.
         *
         *  - adds base link styling to every item
         *  - turns top-level parent links into Alpine dropdown toggles
         *    (replaces Bootstrap's data-bs-toggle="dropdown")
         *  - adds button-styled classes when an editor tags an item with a
         *    'menu-btn' / 'menu-btn-outline' CSS class in the nav-menu screen
         *  - opens social/channel links in a new tab
         *
         * @param array $atts
         * @param \WP_Post $item
         * @param object $args
         * @param int $depth
         * @return array
         */
        add_filter('nav_menu_link_attributes', function ($atts, $item, $args, $depth) {
            $classes = ['nav-link'];

            $has_children = in_array('menu-item-has-children', $item->classes ?? [], true);
            $is_social = 'footer-social' === ($args->theme_location ?? '') || ($args->menu->slug ?? '') === 'footer-menu-socials';

            // Off-canvas links stack full-width, with the (optional) chevron pushed to the right.
            if (! empty($args->offcanvas)) {
                $classes[] = 'flex';
                $classes[] = 'w-full';
                $classes[] = 'items-center';
                $classes[] = 'justify-between';
                $classes[] = 'gap-2';
                $classes[] = 'py-3';
            }

            /**
             * Style a menu item as one of the .btn variants (see
             * resources/css/theme/buttons.css) by giving it matching
             * 'menu-btn-*' CSS class(es) in the menu editor's "CSS
             * Classes" field (Screen Options > CSS Classes) - the field
             * accepts multiple space-separated classes, so a colour
             * variant and the 'menu-btn-sm' size modifier can be combined
             * on the same item, e.g. 'menu-btn-primary menu-btn-sm'.
             *
             * Desktop only - on the off-canvas menu it renders as a plain
             * nav-link like everything else, since a filled button doesn't
             * suit the stacked, full-width accordion layout there.
             *
             * Matched by exact class, not substring, so e.g. a typo'd
             * 'menu-btn-s' is simply ignored rather than silently falling
             * back to a default variant. 'menu-btn-sm' only takes effect
             * alongside a colour variant, same as its plain '.btn-sm'
             * counterpart is meaningless without '.btn'.
             */
            $menu_btn_variants = [
                'menu-btn-primary' => 'btn-primary',
                'menu-btn-secondary' => 'btn-secondary',
                'menu-btn-secondary-inverse' => 'btn-secondary-inverse',
                'menu-btn-secondary-outline' => 'btn-secondary-outline',
                'menu-btn-tertiary' => 'btn-tertiary',
                'menu-btn-dark-blue' => 'btn-dark-blue',
                'menu-btn-gradient' => 'btn-gradient',
                'menu-btn-primary-alt' => 'btn-primary',
                'menu-btn-white' => 'btn-white',
                'menu-btn-white-inverse' => 'btn-white-inverse',
                'menu-btn-outline-primary' => 'btn-white',
            ];
            $menu_btn_modifiers = [
                'menu-btn-sm' => 'btn-sm',
            ];

            $item_classes = $item->classes ?? [];
            $has_btn_variant = false;

            if (empty($args->offcanvas)) {
                foreach ($item_classes as $class) {
                    if (array_key_exists($class, $menu_btn_variants)) {
                        $classes[] = 'btn';
                        $classes[] = $menu_btn_variants[$class];
                        $has_btn_variant = true;
                    }
                }
            }

            if ($has_btn_variant) {
                foreach ($item_classes as $class) {
                    if (array_key_exists($class, $menu_btn_modifiers)) {
                        $classes[] = $menu_btn_modifiers[$class];
                    }
                }
            }

            // Top-level items with a dropdown become Alpine toggles instead of Bootstrap's data-bs-toggle.
            if ($has_children && $depth === 0) {
                $atts['@click.prevent'] = 'open = ! open';
                $atts[':aria-expanded'] = 'open';
                $atts['aria-haspopup'] = 'true';
            }

            if ($is_social) {
                $atts['target'] = '_blank';
                $atts['rel'] = 'noopener noreferrer';
            }

            $atts['class'] = trim(($atts['class'] ?? '') . ' ' . implode(' ', $classes));

            return $atts;
        }, 10, 4);

        /**
         * Display SVG icons in the social links menu, and a chevron
         * indicator after top-level labels that open a dropdown/accordion
         * (so there's a visible cue besides the flyout/accordion itself).
         *
         * The chevron's rotation is driven by NavWalker's `x-data="{ open }"`
         * on the parent <li>, so it works for both the desktop flyout and
         * the offcanvas accordion without any extra markup.
         *
         * @param string $title
         * @param \WP_Post $item
         * @param object $args
         * @param int $depth
         * @return string
         */
        add_filter('nav_menu_item_title', function ($title, $item, $args, $depth) {
            $has_children = in_array('menu-item-has-children', $item->classes ?? [], true);

            if ($has_children && 0 === $depth) {
                $title .= sprintf(
                    '<span class="ml-2 inline-block transition-transform duration-200" :class="{ \'rotate-180\': open }">%s</span>',
                    helpers()->getSvgIcon('chevron-down', 12)
                );
            }

            if ('footer-social' !== ($args->theme_location ?? '') && ($args->menu->slug ?? '') !== 'footer-menu-socials') {
                return $title;
            }

            $social_icons = helpers()->brandIcons();

            foreach ($social_icons as $domain => $icon) {
                if (str_contains($item->url, $domain)) {
                    return sprintf(
                        '<span class="sr-only">%s</span>%s',
                        $title,
                        helpers()->getSvgIcon(esc_attr($icon), 16)
                    );
                }
            }

            return $title;
        }, 10, 4);
    }
}
