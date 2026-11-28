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
add_action( 'customize_register', 'spa_theme_customize_identity' );
add_action( 'customize_register', 'spa_theme_customize_announcement' );
add_action( 'customize_controls_enqueue_scripts', 'spa_theme_customize_controls' );
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

	// Astro reads these from spa/v1/menus/{location}. An assigned location
	// with no menu returns an empty list, not an error.
	register_nav_menus(
		array(
			'primary'       => __( 'Primary', 'spa-headless' ),
			'footer'        => __( 'Menu Footer', 'spa-headless' ),
			'legal'         => __( 'Menu Legal', 'spa-headless' ),
			'footer-social' => __( 'Footer Menu Social', 'spa-headless' ),
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

/**
 * Alternative logo in Site Identity, directly under the core Logo control.
 *
 * Core registers `custom_logo` at priority 8. This control stores an
 * attachment ID (WP_Customize_Media_Control), not a URL. "Show in footer"
 * is hidden until that attachment is set; customize-controls.js updates
 * that live, and the active_callback covers the first paint.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function spa_theme_customize_identity( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_setting(
		'spa_alt_logo',
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'spa_alt_logo',
			array(
				'label'       => __( 'Alternative logo', 'spa-headless' ),
				'description' => __( 'A second wordmark, for example a white logo on the navy footer.', 'spa-headless' ),
				'section'     => 'title_tagline',
				'mime_type'   => 'image',
				'priority'    => 9,
				'button_labels' => array(
					'select'       => __( 'Select logo', 'spa-headless' ),
					'change'       => __( 'Change logo', 'spa-headless' ),
					'remove'       => __( 'Remove', 'spa-headless' ),
					'default'      => __( 'Default', 'spa-headless' ),
					'placeholder'  => __( 'No logo selected', 'spa-headless' ),
					'frame_title'  => __( 'Select logo', 'spa-headless' ),
					'frame_button' => __( 'Choose logo', 'spa-headless' ),
				),
			)
		)
	);

	$wp_customize->add_setting(
		'spa_alt_logo_in_footer',
		array(
			'default'           => false,
			'sanitize_callback' => static function ( $value ): bool {
				return rest_sanitize_boolean( $value );
			},
		)
	);
	$wp_customize->add_control(
		'spa_alt_logo_in_footer',
		array(
			'label'           => __( 'Show in footer', 'spa-headless' ),
			'section'         => 'title_tagline',
			'type'            => 'checkbox',
			'priority'        => 10,
			'active_callback' => static function (): bool {
				return (int) get_theme_mod( 'spa_alt_logo', 0 ) > 0;
			},
		)
	);
}

/**
 * Show "Show in footer" as soon as an alternative logo is chosen, and hide
 * it again when that image is removed, before the change is published.
 */
function spa_theme_customize_controls(): void {
	wp_enqueue_script(
		'spa-customize-controls',
		get_stylesheet_directory_uri() . '/assets/customize-controls.js',
		array( 'customize-controls' ),
		wp_get_theme()->get( 'Version' ),
		true
	);
}

/**
 * Announcement bar above the header. The Astro app reads these theme mods
 * from GET /wp-json/spa/v1/site. There is no dismiss control: the live bar
 * stays until it is turned off here, and it sticks with the header.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function spa_theme_customize_announcement( WP_Customize_Manager $wp_customize ): void {
	$defaults = function_exists( 'spa_headless_announcement_defaults' )
		? spa_headless_announcement_defaults()
		: array(
			'enabled'   => true,
			'message'   => "Is your website underperforming?\nNot ranking on Google or showing up in AI search?",
			'linkLabel' => 'Book your free website audit',
			'url'       => '/contact?enquiry=website-audit',
			'target'    => '',
		);

	$wp_customize->add_section(
		'spa_announcement',
		array(
			'title'    => __( 'Announcement bar', 'spa-headless' ),
			'priority' => 30,
		)
	);

	$wp_customize->add_setting(
		'spa_announcement_enabled',
		array(
			'default'           => $defaults['enabled'],
			'sanitize_callback' => static function ( $value ): bool {
				return rest_sanitize_boolean( $value );
			},
		)
	);
	$wp_customize->add_control(
		'spa_announcement_enabled',
		array(
			'section' => 'spa_announcement',
			'label'   => __( 'Show the announcement bar', 'spa-headless' ),
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'spa_announcement_message',
		array(
			'default'           => $defaults['message'],
			'sanitize_callback' => 'sanitize_textarea_field',
		)
	);
	$wp_customize->add_control(
		'spa_announcement_message',
		array(
			'section'     => 'spa_announcement',
			'label'       => __( 'Message', 'spa-headless' ),
			'description' => __( 'A line break is hidden on desktop and shown on small screens, matching the live bar.', 'spa-headless' ),
			'type'        => 'textarea',
		)
	);

	$wp_customize->add_setting(
		'spa_announcement_link_label',
		array(
			'default'           => $defaults['linkLabel'],
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'spa_announcement_link_label',
		array(
			'section' => 'spa_announcement',
			'label'   => __( 'Link label', 'spa-headless' ),
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'spa_announcement_link_url',
		array(
			'default'           => $defaults['url'],
			'sanitize_callback' => static function ( $value ): string {
				return esc_url_raw( is_string( $value ) ? trim( $value ) : '' );
			},
		)
	);
	$wp_customize->add_control(
		'spa_announcement_link_url',
		array(
			'section'     => 'spa_announcement',
			'label'       => __( 'Link URL', 'spa-headless' ),
			'description' => __( 'A path such as /contact?enquiry=website-audit, or a full URL.', 'spa-headless' ),
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting(
		'spa_announcement_link_target',
		array(
			'default'           => $defaults['target'],
			'sanitize_callback' => static function ( $value ): string {
				return '_blank' === $value ? '_blank' : '';
			},
		)
	);
	$wp_customize->add_control(
		'spa_announcement_link_target',
		array(
			'section' => 'spa_announcement',
			'label'   => __( 'Open link in', 'spa-headless' ),
			'type'    => 'select',
			'choices' => array(
				''       => __( 'Same tab', 'spa-headless' ),
				'_blank' => __( 'A new tab', 'spa-headless' ),
			),
		)
	);
}
