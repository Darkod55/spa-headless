<?php
/**
 * Paste these defines into the Local WP site's wp-config.php
 * (above the "That's all, stop editing!" line).
 *
 * Do not put a real preview token in this file. It is an example and
 * it is tracked in a public repository. Set the token only in wp-config.php
 * and in astro/.env (gitignored).
 *
 * @package Spa_Headless
 */

define( 'SPA_PREVIEW_TOKEN', '' );
define( 'SPA_FRONTEND_URL', 'http://localhost:4321' );

// Comma-separated browser origins allowed to call the WordPress REST API.
define( 'SPA_FRONTEND_ORIGINS', 'http://localhost:4321,http://127.0.0.1:4321' );
