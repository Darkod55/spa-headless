<?php

namespace App\Walkers;

use Walker_Nav_Menu;

/**
 * Nav menu walker.
 *
 * Minimal extension of WordPress's default menu walker. It only exists to:
 *  - add Alpine.js state (`x-data`) to the `<li>` of items that have a
 *    dropdown, since core has no filter for arbitrary `<li>` attributes
 *  - render the submenu `<ul>` with Alpine's `x-show`/`x-transition`
 *    instead of Bootstrap's JS-driven `.dropdown-menu`
 *
 * Everything else (item classes, link attributes, titles) still runs
 * through WordPress's normal filters exactly as before
 * (`nav_menu_css_class`, `nav_menu_link_attributes`, `nav_menu_item_title`),
 * handled in App\Filters\Traits\navs.
 */
class NavWalker extends Walker_Nav_Menu
{
    /**
     * Outputs the opening <ul> for a sub-menu.
     *
     * @param string $output
     * @param int $depth
     * @param object|null $args
     * @return void
     */
    public function start_lvl(&$output, $depth = 0, $args = null)
    {
        $classes = apply_filters('nav_menu_submenu_css_class', ['sub-menu'], $args, $depth);

        $output .= sprintf(
            '<ul x-show="open" x-cloak x-transition @click.outside="open = false" class="%s">',
            esc_attr(implode(' ', $classes))
        );
    }

    /**
     * Starts the element output, adding `x-data` to items with a dropdown.
     *
     * @param string $output
     * @param \WP_Post $item
     * @param int $depth
     * @param object|null $args
     * @param int $id
     * @return void
     */
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
        $before = $output;

        parent::start_el($output, $item, $depth, $args, $id);

        $has_children = in_array('menu-item-has-children', (array) ($item->classes ?? []), true);

        if (! $has_children) {
            return;
        }

        $added = substr($output, strlen($before));
        $added = preg_replace('/^<li\b/', '<li x-data="{ open: false }"', $added, 1);
        $output = $before . $added;
    }
}
