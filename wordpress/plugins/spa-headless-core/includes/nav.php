<?php
/**
 * Primary menu and site identity for the Astro header.
 *
 * Menu item URLs that point at this WordPress host are returned as root-relative
 * paths so the public site does not link back to the CMS. External URLs stay
 * absolute. `current` / `currentAncestor` follow the optional `path` query;
 * Astro also recomputes them for the page it is rendering.
 *
 * @package Spa_Headless
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turn a menu URL into a public href.
 *
 * Same-host links become a path (query and hash kept). Everything else is unchanged.
 */
function spa_headless_public_href( string $url, string $home ): string {
	$url = trim( $url );
	if ( '' === $url || str_starts_with( $url, '#' ) || str_starts_with( $url, '?' ) ) {
		return $url;
	}

	if ( str_starts_with( $url, '/' ) && ! str_starts_with( $url, '//' ) ) {
		return spa_headless_href_with_path( $url );
	}

	$url_parts  = parse_url( $url );
	$home_parts = parse_url( $home );
	if ( ! is_array( $url_parts ) || ! is_array( $home_parts ) ) {
		return $url;
	}

	$url_host  = strtolower( (string) ( $url_parts['host'] ?? '' ) );
	$home_host = strtolower( (string) ( $home_parts['host'] ?? '' ) );
	if ( '' === $url_host || '' === $home_host || $url_host !== $home_host ) {
		return $url;
	}

	$path     = (string) ( $url_parts['path'] ?? '/' );
	$query    = isset( $url_parts['query'] ) ? '?' . $url_parts['query'] : '';
	$fragment = isset( $url_parts['fragment'] ) ? '#' . $url_parts['fragment'] : '';

	return spa_headless_normalize_path( '' === $path ? '/' : $path ) . $query . $fragment;
}

/**
 * Normalize a root-relative href that may already include a query or hash.
 */
function spa_headless_href_with_path( string $href ): string {
	$path     = $href;
	$query    = '';
	$fragment = '';

	$hash = strpos( $path, '#' );
	if ( false !== $hash ) {
		$fragment = substr( $path, $hash );
		$path     = substr( $path, 0, $hash );
	}

	$question = strpos( $path, '?' );
	if ( false !== $question ) {
		$query = substr( $path, $question );
		$path  = substr( $path, 0, $question );
	}

	if ( '' === $path ) {
		$path = '/';
	}

	return spa_headless_normalize_path( $path ) . $query . $fragment;
}

/**
 * Path used for current-page checks. Query and hash are ignored.
 */
function spa_headless_menu_item_path( string $url ): string {
	$path = $url;
	$hash = strpos( $path, '#' );
	if ( false !== $hash ) {
		$path = substr( $path, 0, $hash );
	}
	$question = strpos( $path, '?' );
	if ( false !== $question ) {
		$path = substr( $path, 0, $question );
	}

	if ( preg_match( '#^https?://#i', $path ) ) {
		$parsed = parse_url( $path );
		$path   = is_array( $parsed ) ? (string) ( $parsed['path'] ?? '/' ) : '/';
	}

	if ( '' === $path ) {
		$path = '/';
	}

	return spa_headless_normalize_path( $path );
}

/**
 * Exact path match. Home matches only the front page.
 */
function spa_headless_menu_is_current( string $item_path, string $current_path ): bool {
	if ( '' === $current_path ) {
		return false;
	}

	return spa_headless_menu_item_path( $item_path ) === spa_headless_normalize_path( $current_path );
}

/**
 * The current path is under this item, and the item is not the front page.
 */
function spa_headless_menu_is_ancestor_path( string $item_path, string $current_path ): bool {
	if ( '' === $current_path ) {
		return false;
	}

	$item    = spa_headless_menu_item_path( $item_path );
	$current = spa_headless_normalize_path( $current_path );
	if ( '/' === $item || $item === $current ) {
		return false;
	}

	return str_starts_with( $current, $item . '/' );
}

/**
 * Read width and height from an SVG viewBox when WordPress has no pixel size.
 *
 * @return array{width: int, height: int}
 */
function spa_headless_svg_dimensions( string $svg ): array {
	if ( preg_match( '/viewBox\s*=\s*"\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s*"/i', $svg, $matches ) ) {
		return array(
			'width'  => (int) round( (float) $matches[3] ),
			'height' => (int) round( (float) $matches[4] ),
		);
	}

	return array(
		'width'  => 0,
		'height' => 0,
	);
}

/**
 * Nest a flat menu. Parent 0 is the root.
 *
 * @param list<array{id: int, title: string, url: string, target: string, classes: list<string>, parent: int}> $flat
 * @return list<array<string, mixed>>
 */
function spa_headless_menu_tree( array $flat, string $current_path ): array {
	$by_parent = array();
	foreach ( $flat as $item ) {
		$by_parent[ $item['parent'] ][] = $item;
	}

	$walk = static function ( int $parent_id ) use ( &$walk, $by_parent, $current_path ): array {
		$nodes = array();
		foreach ( $by_parent[ $parent_id ] ?? array() as $item ) {
			$children = $walk( $item['id'] );
			$current  = spa_headless_menu_is_current( $item['url'], $current_path );
			$ancestor = spa_headless_menu_is_ancestor_path( $item['url'], $current_path );
			foreach ( $children as $child ) {
				if ( ! empty( $child['current'] ) || ! empty( $child['currentAncestor'] ) ) {
					$ancestor = true;
				}
			}
			if ( $current ) {
				$ancestor = false;
			}

			$nodes[] = array(
				'id'              => $item['id'],
				'title'           => $item['title'],
				'url'             => $item['url'],
				'target'          => $item['target'],
				'classes'         => $item['classes'],
				'parent'          => $item['parent'],
				'children'        => $children,
				'current'         => $current,
				'currentAncestor' => $ancestor,
			);
		}

		return $nodes;
	};

	return $walk( 0 );
}

/**
 * @return list<array<string, mixed>>
 */
function spa_headless_menu_for_location( string $location, string $current_path ): array {
	$locations = get_nav_menu_locations();
	$menu_id   = isset( $locations[ $location ] ) ? (int) $locations[ $location ] : 0;
	if ( $menu_id <= 0 ) {
		return array();
	}

	$items = wp_get_nav_menu_items( $menu_id );
	if ( ! is_array( $items ) ) {
		return array();
	}

	$home = home_url( '/' );
	$flat = array();
	foreach ( $items as $item ) {
		if ( ! $item instanceof WP_Post ) {
			continue;
		}

		$classes = array();
		$raw     = $item->classes ?? array();
		if ( is_array( $raw ) ) {
			foreach ( $raw as $class ) {
				if ( is_string( $class ) && '' !== $class ) {
					$classes[] = $class;
				}
			}
		}

		$flat[] = array(
			'id'      => (int) $item->ID,
			'title'   => wp_specialchars_decode( (string) $item->title, ENT_QUOTES ),
			'url'     => spa_headless_public_href( (string) $item->url, $home ),
			'target'  => is_string( $item->target ) ? $item->target : '',
			'classes' => $classes,
			'parent'  => (int) $item->menu_item_parent,
		);
	}

	return spa_headless_menu_tree( $flat, $current_path );
}

/**
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function spa_headless_rest_menu( WP_REST_Request $request ) {
	$location  = (string) $request->get_param( 'location' );
	$registered = get_registered_nav_menus();
	if ( ! isset( $registered[ $location ] ) ) {
		return new WP_Error(
			'spa_menu_location',
			'That menu location is not registered.',
			array( 'status' => 404 )
		);
	}

	// Registered but unassigned: items is an empty list, not an error.

	$path = $request->get_param( 'path' );
	$current = is_string( $path ) ? $path : '';

	return rest_ensure_response(
		array(
			'location' => $location,
			'items'    => spa_headless_menu_for_location( $location, $current ),
		)
	);
}

/**
 * Footer logo: the alternative logo when it is set and "Show in footer" is
 * ticked, otherwise the main custom logo. Null when neither has a URL.
 *
 * @param array<string, mixed>|null $alt
 * @param bool                      $in_footer
 * @param array<string, mixed>|null $logo
 * @return array<string, mixed>|null
 */
function spa_headless_resolve_footer_logo( ?array $alt, bool $in_footer, ?array $logo ): ?array {
	if ( $in_footer && isset( $alt['url'] ) && is_string( $alt['url'] ) && '' !== $alt['url'] ) {
		return $alt;
	}
	if ( isset( $logo['url'] ) && is_string( $logo['url'] ) && '' !== $logo['url'] ) {
		return $logo;
	}

	return null;
}

/**
 * Attachment as the logo object shared by logo, altLogo, and footerLogo.
 *
 * SVG files often have no pixel metadata. The viewBox supplies width and height
 * so the header can keep the wordmark's aspect ratio. The URL stays on the
 * WordPress host: the file lives in the media library, not in the Astro build.
 *
 * @return array<string, mixed>|null
 */
function spa_headless_logo_from_attachment( int $attachment_id ): ?array {
	if ( $attachment_id <= 0 ) {
		return null;
	}

	$url = wp_get_attachment_url( $attachment_id );
	if ( ! is_string( $url ) || '' === $url ) {
		return null;
	}

	$meta   = wp_get_attachment_metadata( $attachment_id );
	$width  = is_array( $meta ) && isset( $meta['width'] ) ? (int) $meta['width'] : 0;
	$height = is_array( $meta ) && isset( $meta['height'] ) ? (int) $meta['height'] : 0;
	$mime   = get_post_mime_type( $attachment_id );
	$mime   = is_string( $mime ) ? $mime : '';

	if ( ( $width <= 0 || $height <= 0 ) && 'image/svg+xml' === $mime ) {
		$file = get_attached_file( $attachment_id );
		if ( is_string( $file ) && is_readable( $file ) ) {
			$svg = file_get_contents( $file );
			if ( is_string( $svg ) ) {
				$size   = spa_headless_svg_dimensions( $svg );
				$width  = $size['width'];
				$height = $size['height'];
			}
		}
	}

	$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
	if ( ! is_string( $alt ) || '' === trim( $alt ) ) {
		$alt = get_bloginfo( 'name' );
	}

	return array(
		'id'     => $attachment_id,
		'url'    => $url,
		'width'  => $width > 0 ? $width : null,
		'height' => $height > 0 ? $height : null,
		'alt'    => $alt,
		'mime'   => $mime,
	);
}

/**
 * Site Identity → Logo (`custom_logo`).
 *
 * @return array<string, mixed>|null
 */
function spa_headless_site_logo(): ?array {
	return spa_headless_logo_from_attachment( (int) get_theme_mod( 'custom_logo' ) );
}

/**
 * Site Identity → Alternative logo (`spa_alt_logo`), whether or not the
 * footer checkbox is ticked.
 *
 * @return array<string, mixed>|null
 */
function spa_headless_alt_logo(): ?array {
	return spa_headless_logo_from_attachment( (int) get_theme_mod( 'spa_alt_logo', 0 ) );
}

/**
 * The logo the footer should render. Astro substitutes the bundled white
 * wordmark when this is null.
 *
 * @return array<string, mixed>|null
 */
function spa_headless_footer_logo(): ?array {
	$in_footer = (bool) get_theme_mod( 'spa_alt_logo_in_footer', false );

	return spa_headless_resolve_footer_logo(
		spa_headless_alt_logo(),
		$in_footer,
		spa_headless_site_logo()
	);
}

/**
 * Defaults match the live announcement bar. A newline in the message is the
 * mobile-only line break; desktop CSS hides that break.
 *
 * @return array{enabled: bool, message: string, linkLabel: string, url: string, target: string}
 */
function spa_headless_announcement_defaults(): array {
	return array(
		'enabled'   => true,
		'message'   => "Is your website underperforming?\nNot ranking on Google or showing up in AI search?",
		'linkLabel' => 'Book your free website audit',
		'url'       => '/contact?enquiry=website-audit',
		'target'    => '',
	);
}

/**
 * @param array<string, mixed> $values
 * @return array{enabled: bool, message: string, linkLabel: string, url: string, target: string}
 */
function spa_headless_format_announcement( array $values, string $home ): array {
	$defaults = spa_headless_announcement_defaults();
	$message  = isset( $values['message'] ) && is_string( $values['message'] ) ? $values['message'] : $defaults['message'];
	$label    = isset( $values['linkLabel'] ) && is_string( $values['linkLabel'] ) ? $values['linkLabel'] : $defaults['linkLabel'];
	$url      = isset( $values['url'] ) && is_string( $values['url'] ) ? $values['url'] : $defaults['url'];
	$target   = ( isset( $values['target'] ) && '_blank' === $values['target'] ) ? '_blank' : '';

	return array(
		'enabled'   => array_key_exists( 'enabled', $values ) ? (bool) $values['enabled'] : $defaults['enabled'],
		'message'   => $message,
		'linkLabel' => $label,
		'url'       => spa_headless_public_href( $url, $home ),
		'target'    => $target,
	);
}

/**
 * Site Identity → Site Icon, via get_site_icon_url().
 *
 * Sizes match wp_site_icon(): 32 and 192 for rel=icon, 180 for apple-touch,
 * 270 for the Windows tile image. An SVG has no resized derivatives, so every
 * size is the original file URL. The Astro layout collapses that to one tag.
 *
 * @return array{mime: string, sizes: array{32: string|null, 180: string|null, 192: string|null, 270: string|null}}|null
 */
function spa_headless_site_icon(): ?array {
	if ( ! has_site_icon() ) {
		return null;
	}

	$id   = (int) get_option( 'site_icon' );
	$mime = get_post_mime_type( $id );
	$mime = is_string( $mime ) ? $mime : '';

	$sizes = array();
	foreach ( array( 32, 180, 192, 270 ) as $size ) {
		$url              = get_site_icon_url( $size );
		$sizes[ (string) $size ] = is_string( $url ) && '' !== $url ? $url : null;
	}

	$present = array_filter( $sizes );
	if ( array() === $present ) {
		return null;
	}

	return array(
		'mime'  => $mime,
		'sizes' => array(
			'32'  => $sizes['32'],
			'180' => $sizes['180'],
			'192' => $sizes['192'],
			'270' => $sizes['270'],
		),
	);
}

/**
 * Theme mods from Appearance → Customize → Announcement bar.
 *
 * @return array{enabled: bool, message: string, linkLabel: string, url: string, target: string}
 */
function spa_headless_announcement(): array {
	$defaults = spa_headless_announcement_defaults();

	return spa_headless_format_announcement(
		array(
			'enabled'   => get_theme_mod( 'spa_announcement_enabled', $defaults['enabled'] ),
			'message'   => get_theme_mod( 'spa_announcement_message', $defaults['message'] ),
			'linkLabel' => get_theme_mod( 'spa_announcement_link_label', $defaults['linkLabel'] ),
			'url'       => get_theme_mod( 'spa_announcement_link_url', $defaults['url'] ),
			'target'    => get_theme_mod( 'spa_announcement_link_target', $defaults['target'] ),
		),
		home_url( '/' )
	);
}

/**
 * @return WP_REST_Response
 */
function spa_headless_rest_site(): WP_REST_Response {
	return rest_ensure_response(
		array(
			'title'        => get_bloginfo( 'name' ),
			'description'  => get_bloginfo( 'description' ),
			'url'          => home_url( '/' ),
			'logo'         => spa_headless_site_logo(),
			'altLogo'      => spa_headless_alt_logo(),
			'footerLogo'   => spa_headless_footer_logo(),
			'siteIcon'     => spa_headless_site_icon(),
			'announcement' => spa_headless_announcement(),
		)
	);
}
