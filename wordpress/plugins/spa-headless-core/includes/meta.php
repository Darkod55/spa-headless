<?php
/**
 * Public content meta.
 *
 * Registered with show_in_rest instead of ACF. auth_callback returns true
 * because these values are public page content: WordPress calls that callback
 * before including meta in a REST response, including anonymous reads.
 * Do not reuse this callback for anything that is not meant to be public.
 *
 * @package Spa_Headless
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register meta on every headless post type.
 */
function spa_headless_register_meta(): void {
	foreach ( spa_headless_post_types() as $post_type ) {
		register_post_meta(
			$post_type,
			'spa_eyebrow',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => 'spa_headless_public_meta_auth',
			)
		);

		register_post_meta(
			$post_type,
			'spa_hide_title',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'auth_callback'     => 'spa_headless_public_meta_auth',
			)
		);
	}
}

/**
 * Public meta is readable without authentication.
 */
function spa_headless_public_meta_auth(): bool {
	return true;
}

/**
 * Values returned on spa/v1 content responses.
 *
 * @return array{spa_eyebrow: string, spa_hide_title: bool}
 */
function spa_headless_public_meta( int $post_id ): array {
	return array(
		'spa_eyebrow'    => (string) get_post_meta( $post_id, 'spa_eyebrow', true ),
		'spa_hide_title' => (bool) get_post_meta( $post_id, 'spa_hide_title', true ),
	);
}
