<?php
/**
 * CORS for the Astro dev server.
 *
 * WordPress reflects any Origin on REST responses. This plugin replaces that
 * with an allowlist so only the local front end (and origins in
 * SPA_FRONTEND_ORIGINS) can read the API from a browser.
 *
 * @package Spa_Headless
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return list<string>
 */
function spa_headless_allowed_origins(): array {
	$origins = array(
		'http://localhost:4321',
		'http://127.0.0.1:4321',
	);

	if ( defined( 'SPA_FRONTEND_ORIGINS' ) ) {
		foreach ( explode( ',', (string) SPA_FRONTEND_ORIGINS ) as $origin ) {
			$origin = trim( $origin );
			if ( '' !== $origin ) {
				$origins[] = $origin;
			}
		}
	}

	return array_values( array_unique( $origins ) );
}

/**
 * Replace the default reflect-any-origin REST CORS handler.
 */
function spa_headless_register_cors(): void {
	remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
	add_filter( 'rest_pre_serve_request', 'spa_headless_send_cors_headers' );
	add_filter( 'rest_allowed_cors_headers', 'spa_headless_allow_preview_header' );
}

/**
 * @param list<string> $headers
 * @return list<string>
 */
function spa_headless_allow_preview_header( array $headers ): array {
	$headers[] = 'X-SPA-Preview-Token';
	return $headers;
}

/**
 * @param mixed $served
 * @return mixed
 */
function spa_headless_send_cors_headers( $served ) {
	if ( headers_sent() ) {
		return $served;
	}

	$origin = get_http_origin();
	if ( ! is_string( $origin ) || '' === $origin ) {
		return $served;
	}

	if ( ! in_array( $origin, spa_headless_allowed_origins(), true ) ) {
		return $served;
	}

	header( 'Access-Control-Allow-Origin: ' . $origin );
	header( 'Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, PATCH, DELETE' );
	header( 'Access-Control-Allow-Credentials: true' );
	header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce, X-SPA-Preview-Token' );
	header( 'Vary: Origin', false );

	return $served;
}

add_action( 'rest_api_init', 'spa_headless_register_cors', 15 );
