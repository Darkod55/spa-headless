<?php
/**
 * Plugin Name: Square Peg Headless Core
 * Description: REST block payload, public meta, CORS for the Astro dev server, and draft preview for the headless front end.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.2
 * Author: The Square Peg Agency
 * Text Domain: spa-headless
 * License: GPL-2.0-or-later
 *
 * @package Spa_Headless
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SPA_HEADLESS_CORE_VERSION', '0.1.0' );
define( 'SPA_HEADLESS_CORE_FILE', __FILE__ );
define( 'SPA_HEADLESS_CORE_DIR', plugin_dir_path( __FILE__ ) );

require_once SPA_HEADLESS_CORE_DIR . 'includes/blocks.php';
require_once SPA_HEADLESS_CORE_DIR . 'includes/cors.php';
require_once SPA_HEADLESS_CORE_DIR . 'includes/meta.php';
require_once SPA_HEADLESS_CORE_DIR . 'includes/routes.php';

add_action( 'init', 'spa_headless_register_meta' );
add_action( 'rest_api_init', 'spa_headless_register_blocks_field' );
add_action( 'rest_api_init', 'spa_headless_register_routes' );
add_action( 'admin_notices', 'spa_headless_blockstudio_notice' );

/**
 * BlockStudio is installed separately in Local WP. Blocks live in the theme;
 * this notice only fires when that plugin is missing.
 */
function spa_headless_blockstudio_notice(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	if ( class_exists( '\Blockstudio\Build' ) ) {
		return;
	}

	echo '<div class="notice notice-warning"><p>';
	echo esc_html__( 'Square Peg blocks are registered by BlockStudio from the active theme. Activate BlockStudio and the Square Peg Headless theme.', 'spa-headless' );
	echo '</p></div>';
}
