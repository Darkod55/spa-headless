<?php
/**
 * Square Peg Headless theme.
 *
 * BlockStudio registers every block.json under blockstudio/ in the active
 * theme. Headless REST behaviour lives in the spa-headless-core plugin so
 * it does not depend on which theme is active.
 *
 * @package Spa_Headless
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', 'spa_theme_setup' );
add_action( 'wp_enqueue_scripts', 'spa_theme_assets' );
add_action( 'wp_head', 'spa_theme_noindex', 0 );
add_filter( 'block_categories_all', 'spa_theme_block_category' );
add_filter( 'blockstudio/settings/tailwind/config', 'spa_theme_tailwind_config' );
add_filter( 'upload_mimes', 'spa_theme_allow_svg_mime' );
add_filter( 'wp_check_filetype_and_ext', 'spa_theme_fix_svg_mime', 10, 4 );

/**
 * Theme supports. The block editor is the authoring surface.
 */
function spa_theme_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/preview.css' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);

	// The Astro header reads this location from spa/v1/menus/primary.
	register_nav_menus(
		array(
			'primary' => __( 'Primary', 'spa-headless' ),
		)
	);

	/*
	 * Appearance → Customize → Site Identity → Logo.
	 * SVG is supported when WordPress accepts the upload; width and height
	 * stay flexible so a wordmark is not cropped.
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'               => 60,
			'width'                => 232,
			'flex-height'          => true,
			'flex-width'           => true,
			'unlink-homepage-logo' => true,
		)
	);
}

/**
 * Allow SVG uploads (site logo, media library). Only use trusted files —
 * SVG can contain script.
 *
 * @param array<string, string> $mimes
 * @return array<string, string>
 */
function spa_theme_allow_svg_mime( array $mimes ): array {
	$mimes['svg'] = 'image/svg+xml';

	return $mimes;
}

/**
 * WordPress's real MIME sniffer rejects SVG; restore the type after the
 * extension check when the file is an SVG.
 *
 * @param array{ext?: string|false, type?: string|false, proper_filename?: string|false} $data
 * @param string                                                                        $file
 * @param string                                                                        $filename
 * @param array<string, string>|null                                                    $mimes
 * @return array{ext?: string|false, type?: string|false, proper_filename?: string|false}
 */
function spa_theme_fix_svg_mime( array $data, string $file, string $filename, $mimes ): array {
	unset( $file, $mimes );

	if ( ! empty( $data['ext'] ) && ! empty( $data['type'] ) ) {
		return $data;
	}

	if ( 'svg' !== strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) ) ) {
		return $data;
	}

	$data['ext']  = 'svg';
	$data['type'] = 'image/svg+xml';

	return $data;
}

/**
 * Plain CSS so the WordPress preview is readable before Tailwind runs.
 */
function spa_theme_assets(): void {
	wp_enqueue_style(
		'spa-headless-preview',
		get_stylesheet_directory_uri() . '/assets/preview.css',
		array(),
		wp_get_theme()->get( 'Version' )
	);
}

/**
 * The CMS host is not the public site. Keep it out of search indexes.
 */
function spa_theme_noindex(): void {
	echo "<meta name=\"robots\" content=\"noindex, nofollow\">\n";
}

/**
 * Editor category for spa/* blocks.
 *
 * @param array<int, array<string, mixed>> $categories
 * @return array<int, array<string, mixed>>
 */
function spa_theme_block_category( array $categories ): array {
	$categories[] = array(
		'slug'  => 'spa',
		'title' => __( 'Square Peg', 'spa-headless' ),
		'icon'  => 'layout',
	);

	return $categories;
}

/**
 * Point BlockStudio's Tailwind v4 compiler at this theme's style.css.
 */
function spa_theme_tailwind_config(): string {
	$style = get_stylesheet_directory() . '/style.css';
	if ( ! is_readable( $style ) ) {
		return '';
	}

	$contents = file_get_contents( $style );

	return is_string( $contents ) ? $contents : '';
}

/**
 * Section classes shared by every block template.
 *
 * The section field (blockstudio/fields/section) stores removeBottomPadding
 * and accentBg as top-level attributes because the group has no id.
 *
 * @param array<string, mixed> $attributes
 * @return array{0: string, 1: string}
 */
function spa_section_classes( array $attributes, string $extra_section = '', string $extra_container = '' ): array {
	$section   = ! empty( $attributes['accentBg'] ) ? 'spa-section has-accent' : 'spa-section';
	$container = 'spa-container';

	if ( ! empty( $attributes['removeBottomPadding'] ) ) {
		$container .= ' is-flush';
	}

	if ( '' !== $extra_section ) {
		$section .= ' ' . $extra_section;
	}

	if ( '' !== $extra_container ) {
		$container .= ' ' . $extra_container;
	}

	return array( $section, $container );
}

/**
 * JSON for an InnerBlocks attribute, escaped for an HTML attribute.
 *
 * @param array<int|string, mixed> $data
 */
function spa_json_attr( array $data ): string {
	$json = wp_json_encode( $data );
	if ( ! is_string( $json ) ) {
		return '';
	}

	return esc_attr( $json );
}

/**
 * BlockStudio file fields may arrive as one object or a one-item list.
 *
 * @param mixed $file
 * @return array{url: string, alt: string}
 */
function spa_file_values( $file ): array {
	if ( ! is_array( $file ) ) {
		return array(
			'url' => '',
			'alt' => '',
		);
	}

	if ( isset( $file[0] ) && is_array( $file[0] ) ) {
		return spa_file_values( $file[0] );
	}

	$url = isset( $file['url'] ) && is_string( $file['url'] ) ? $file['url'] : '';
	$alt = isset( $file['alt'] ) && is_string( $file['alt'] ) ? $file['alt'] : '';

	return array(
		'url' => $url,
		'alt' => $alt,
	);
}

/**
 * @param mixed $link
 * @return array{url: string, title: string, new_tab: bool}
 */
function spa_link_values( $link ): array {
	if ( ! is_array( $link ) ) {
		return array(
			'url'     => '',
			'title'   => '',
			'new_tab' => false,
		);
	}

	$url   = isset( $link['url'] ) && is_string( $link['url'] ) ? $link['url'] : '';
	$title = isset( $link['title'] ) && is_string( $link['title'] ) ? $link['title'] : '';

	return array(
		'url'     => $url,
		'title'   => $title,
		'new_tab' => ! empty( $link['opensInNewTab'] ),
	);
}
