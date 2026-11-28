<?php
/**
 * Read-only routes the Astro app uses to resolve a public path.
 *
 * `blocks` is only on these spa/v1 responses. It is not a wp/v2 field:
 * the block editor reserves that key on the post entity.
 *
 * @package Spa_Headless
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register spa/v1 routes.
 */
function spa_headless_register_routes(): void {
	register_rest_route(
		'spa/v1',
		'/routes',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => 'spa_headless_rest_routes',
		)
	);

	register_rest_route(
		'spa/v1',
		'/content',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => 'spa_headless_rest_content',
			'args'                => array(
				'path' => array(
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);

	register_rest_route(
		'spa/v1',
		'/menus/(?P<location>[a-z0-9_-]+)',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => 'spa_headless_rest_menu',
			'args'                => array(
				'location' => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				),
				'path'     => array(
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);

	register_rest_route(
		'spa/v1',
		'/site',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => 'spa_headless_rest_site',
		)
	);

	register_rest_route(
		'spa/v1',
		'/preview/(?P<id>\d+)',
		array(
			'methods'             => 'GET',
			'permission_callback' => 'spa_headless_preview_permission',
			'callback'            => 'spa_headless_rest_preview',
			'args'                => array(
				'id' => array(
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}

/**
 * Collapse a permalink path to the form Astro uses (no trailing slash).
 */
function spa_headless_normalize_path( string $path ): string {
	$trimmed = trim( $path, '/' );
	if ( '' === $trimmed ) {
		return '/';
	}

	return '/' . $trimmed;
}

/**
 * @return WP_REST_Response
 */
function spa_headless_rest_routes(): WP_REST_Response {
	return rest_ensure_response(
		array(
			'routes' => spa_headless_collect_routes(),
		)
	);
}

/**
 * @return list<array{path: string, id: int, type: string, slug: string}>
 */
function spa_headless_collect_routes(): array {
	$posts = get_posts(
		array(
			'post_type'      => spa_headless_post_types(),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);

	$routes = array();
	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}

		$permalink = get_permalink( $post );
		$path      = is_string( $permalink ) ? (string) wp_parse_url( $permalink, PHP_URL_PATH ) : '/';

		$routes[] = array(
			'path' => spa_headless_normalize_path( $path ),
			'id'   => (int) $post->ID,
			'type' => $post->post_type,
			'slug' => $post->post_name,
		);
	}

	return $routes;
}

/**
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function spa_headless_rest_content( WP_REST_Request $request ) {
	$path = spa_headless_normalize_path( (string) $request->get_param( 'path' ) );
	$post = spa_headless_published_post_for_path( $path );

	if ( ! $post instanceof WP_Post ) {
		return new WP_Error(
			'spa_not_found',
			'No published content at that path.',
			array( 'status' => 404 )
		);
	}

	return rest_ensure_response( spa_headless_serialize_post( $post ) );
}

/**
 * Preview is denied unless SPA_PREVIEW_TOKEN is set and the header matches.
 * An empty token in wp-config.php means preview stays closed.
 *
 * @param WP_REST_Request $request
 * @return true|WP_Error
 */
function spa_headless_preview_permission( WP_REST_Request $request ) {
	$expected = defined( 'SPA_PREVIEW_TOKEN' ) ? (string) SPA_PREVIEW_TOKEN : '';
	$given    = $request->get_header( 'x-spa-preview-token' );

	if ( '' === $expected || ! is_string( $given ) || strlen( $given ) !== strlen( $expected ) || ! hash_equals( $expected, $given ) ) {
		return new WP_Error(
			'spa_preview_unauthorized',
			'Preview requires a matching SPA_PREVIEW_TOKEN header.',
			array( 'status' => 401 )
		);
	}

	return true;
}

/**
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function spa_headless_rest_preview( WP_REST_Request $request ) {
	$post = get_post( (int) $request->get_param( 'id' ) );
	if ( ! $post instanceof WP_Post ) {
		return new WP_Error(
			'spa_preview_not_found',
			'Content not found.',
			array( 'status' => 404 )
		);
	}

	if ( ! in_array( $post->post_type, spa_headless_post_types(), true ) ) {
		return new WP_Error(
			'spa_preview_type',
			'This post type is not exposed to the headless front end.',
			array( 'status' => 404 )
		);
	}

	$allowed = array( 'publish', 'draft', 'pending', 'future', 'private' );
	if ( ! in_array( $post->post_status, $allowed, true ) ) {
		return new WP_Error(
			'spa_preview_status',
			'This status cannot be previewed.',
			array( 'status' => 404 )
		);
	}

	return rest_ensure_response( spa_headless_serialize_post( $post ) );
}

/**
 * Resolve a public path to a published post.
 *
 * url_to_postid follows the site's permalink structure, so slugs stay
 * identical to WordPress (including /blog/%postname%/). The slug query is a
 * fallback when rewrite rules have not been flushed yet.
 */
function spa_headless_published_post_for_path( string $path ): ?WP_Post {
	$post_id = spa_headless_post_id_for_path( $path );
	if ( $post_id <= 0 ) {
		return null;
	}

	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
		return null;
	}

	if ( ! in_array( $post->post_type, spa_headless_post_types(), true ) ) {
		return null;
	}

	return $post;
}

/**
 * Find a post id for a normalized path.
 */
function spa_headless_post_id_for_path( string $path ): int {
	$normalized = spa_headless_normalize_path( $path );

	if ( '/' === $normalized ) {
		$front = (int) get_option( 'page_on_front' );
		if ( $front > 0 ) {
			return $front;
		}
	}

	$candidates = array(
		home_url( $normalized ),
		home_url( trailingslashit( $normalized ) ),
	);

	foreach ( $candidates as $url ) {
		$id = url_to_postid( $url );
		if ( $id > 0 ) {
			return $id;
		}
	}

	$slug  = basename( $normalized );
	$found = get_posts(
		array(
			'name'           => $slug,
			'post_type'      => spa_headless_post_types(),
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'no_found_rows'  => true,
		)
	);

	foreach ( $found as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}

		$permalink = get_permalink( $post );
		$post_path = is_string( $permalink ) ? (string) wp_parse_url( $permalink, PHP_URL_PATH ) : '';
		if ( spa_headless_normalize_path( $post_path ) === $normalized ) {
			return (int) $post->ID;
		}
	}

	return 0;
}

/**
 * @return array<string, mixed>
 */
function spa_headless_serialize_post( WP_Post $post ): array {
	$permalink = get_permalink( $post );
	$path      = is_string( $permalink ) ? (string) wp_parse_url( $permalink, PHP_URL_PATH ) : '/';
	$excerpt   = '' !== trim( $post->post_excerpt ) ? wp_strip_all_tags( $post->post_excerpt ) : '';

	return array(
		'id'      => (int) $post->ID,
		'type'    => $post->post_type,
		'slug'    => $post->post_name,
		'path'    => spa_headless_normalize_path( $path ),
		'status'  => $post->post_status,
		'title'   => get_the_title( $post ),
		'excerpt' => $excerpt,
		'link'    => is_string( $permalink ) ? $permalink : '',
		'meta'    => spa_headless_public_meta( (int) $post->ID ),
		'blocks'  => spa_headless_blocks_from_content( $post->post_content ),
	);
}
