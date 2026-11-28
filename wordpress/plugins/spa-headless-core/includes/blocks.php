<?php
/**
 * Turn parse_blocks() into the JSON tree the Astro renderer consumes.
 *
 * @package Spa_Headless
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post types that expose the `blocks` REST field and the spa/v1 routes.
 *
 * @return list<string>
 */
function spa_headless_post_types(): array {
	$types = array( 'post', 'page' );

	/**
	 * Add a custom post type when it should render on the Astro site.
	 *
	 * @param list<string> $types
	 */
	$filtered = apply_filters( 'spa_headless_post_types', $types );

	if ( ! is_array( $filtered ) ) {
		return $types;
	}

	$clean = array();
	foreach ( $filtered as $type ) {
		if ( is_string( $type ) && '' !== $type ) {
			$clean[] = $type;
		}
	}

	return array_values( array_unique( $clean ) );
}

/**
 * BlockStudio file value (or an attachment id) as the hero image payload.
 *
 * Width and height come from the object, then from sizes.full, then from
 * attachment metadata when WordPress is loaded.
 *
 * @param mixed $value
 * @return array{id: int, url: string, alt: string, width: int|null, height: int|null}|null
 */
function spa_headless_prepare_hero_image( mixed $value ): ?array {
	if ( is_array( $value ) && isset( $value[0] ) && is_array( $value[0] ) ) {
		return spa_headless_prepare_hero_image( $value[0] );
	}

	if ( is_numeric( $value ) ) {
		return spa_headless_hero_image_from_attachment( (int) $value );
	}

	if ( ! is_array( $value ) ) {
		return null;
	}

	$id = 0;
	if ( isset( $value['id'] ) && is_numeric( $value['id'] ) ) {
		$id = (int) $value['id'];
	} elseif ( isset( $value['ID'] ) && is_numeric( $value['ID'] ) ) {
		$id = (int) $value['ID'];
	}

	$url = isset( $value['url'] ) && is_string( $value['url'] ) ? $value['url'] : '';
	$alt = isset( $value['alt'] ) && is_string( $value['alt'] ) ? $value['alt'] : '';
	$width  = isset( $value['width'] ) && is_numeric( $value['width'] ) ? (int) $value['width'] : 0;
	$height = isset( $value['height'] ) && is_numeric( $value['height'] ) ? (int) $value['height'] : 0;

	$sizes = isset( $value['sizes'] ) && is_array( $value['sizes'] ) ? $value['sizes'] : array();
	if ( ( $width <= 0 || $height <= 0 ) && isset( $sizes['full'] ) && is_array( $sizes['full'] ) ) {
		$width  = isset( $sizes['full']['width'] ) ? (int) $sizes['full']['width'] : $width;
		$height = isset( $sizes['full']['height'] ) ? (int) $sizes['full']['height'] : $height;
	}
	if ( ( $width <= 0 || $height <= 0 ) && isset( $sizes['full-width'] ) ) {
		$width  = (int) $sizes['full-width'];
		$height = isset( $sizes['full-height'] ) ? (int) $sizes['full-height'] : $height;
	}

	if ( '' === $url && $id > 0 ) {
		$from_attachment = spa_headless_hero_image_from_attachment( $id );
		if ( null !== $from_attachment ) {
			if ( '' === $alt ) {
				$alt = $from_attachment['alt'];
			}
			$url = $from_attachment['url'];
			if ( $width <= 0 ) {
				$width = (int) ( $from_attachment['width'] ?? 0 );
			}
			if ( $height <= 0 ) {
				$height = (int) ( $from_attachment['height'] ?? 0 );
			}
		}
	} elseif ( $id > 0 && ( $width <= 0 || $height <= 0 ) && function_exists( 'wp_get_attachment_metadata' ) ) {
		$meta = wp_get_attachment_metadata( $id );
		if ( is_array( $meta ) ) {
			if ( $width <= 0 && isset( $meta['width'] ) ) {
				$width = (int) $meta['width'];
			}
			if ( $height <= 0 && isset( $meta['height'] ) ) {
				$height = (int) $meta['height'];
			}
		}
	}

	if ( '' === $url ) {
		return null;
	}

	return array(
		'id'     => $id,
		'url'    => $url,
		'alt'    => $alt,
		'width'  => $width > 0 ? $width : null,
		'height' => $height > 0 ? $height : null,
	);
}

/**
 * @return array{id: int, url: string, alt: string, width: int|null, height: int|null}|null
 */
function spa_headless_hero_image_from_attachment( int $attachment_id ): ?array {
	if ( $attachment_id <= 0 || ! function_exists( 'wp_get_attachment_url' ) ) {
		return null;
	}

	$url = wp_get_attachment_url( $attachment_id );
	if ( ! is_string( $url ) || '' === $url ) {
		return null;
	}

	$alt = '';
	if ( function_exists( 'get_post_meta' ) ) {
		$stored = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		$alt    = is_string( $stored ) ? $stored : '';
	}

	$width  = 0;
	$height = 0;
	if ( function_exists( 'wp_get_attachment_metadata' ) ) {
		$meta = wp_get_attachment_metadata( $attachment_id );
		if ( is_array( $meta ) ) {
			$width  = isset( $meta['width'] ) ? (int) $meta['width'] : 0;
			$height = isset( $meta['height'] ) ? (int) $meta['height'] : 0;
		}
	}

	return array(
		'id'     => $attachment_id,
		'url'    => $url,
		'alt'    => $alt,
		'width'  => $width > 0 ? $width : null,
		'height' => $height > 0 ? $height : null,
	);
}

/**
 * @param array<int, mixed> $blocks Parsed blocks from parse_blocks().
 * @return list<array{blockName: string|null, attrs: array<string, mixed>, innerBlocks: list<mixed>, innerHTML: string, innerContent: list<string|null>}>
 */
function spa_headless_normalize_blocks( array $blocks ): array {
	$normalized = array();

	foreach ( $blocks as $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}

		$name        = $block['blockName'] ?? null;
		$inner_html  = isset( $block['innerHTML'] ) && is_string( $block['innerHTML'] ) ? $block['innerHTML'] : '';
		$raw_inner   = isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ? $block['innerBlocks'] : array();
		$inner_blocks = spa_headless_normalize_blocks( $raw_inner );

		if ( null === $name && '' === trim( $inner_html ) && array() === $inner_blocks ) {
			continue;
		}

		$inner_content = array();
		if ( isset( $block['innerContent'] ) && is_array( $block['innerContent'] ) ) {
			foreach ( $block['innerContent'] as $piece ) {
				if ( null === $piece || is_string( $piece ) ) {
					$inner_content[] = $piece;
				}
			}
		}

		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		$nested_image = null;
		if ( isset( $attrs['blockstudio']['attributes']['image'] ) ) {
			$nested_image = $attrs['blockstudio']['attributes']['image'];
		}
		$had_image = array_key_exists( 'image', $attrs );
		spa_headless_flatten_blockstudio_attributes( $attrs );

		if ( is_string( $name ) && in_array( $name, array( 'spa/hero-banner-main', 'spa/hero-banner-default' ), true ) ) {
			$raw_image = $had_image ? ( $attrs['image'] ?? null ) : $nested_image;
			if ( ( null === $raw_image || '' === $raw_image || array() === $raw_image ) && null !== $nested_image ) {
				$raw_image = $nested_image;
			}
			$attrs['image'] = spa_headless_prepare_hero_image( $raw_image );
		}

		$normalized[] = array(
			'blockName'    => is_string( $name ) ? $name : null,
			'attrs'        => $attrs,
			'innerBlocks'  => $inner_blocks,
			'innerHTML'    => $inner_html,
			'innerContent' => $inner_content,
		);
	}

	return $normalized;
}

/**
 * Copy BlockStudio extension values onto the attribute bag.
 *
 * `set` is applied on render in WordPress and is not stored in innerHTML.
 * The values live at attrs.blockstudio.attributes. The nest is kept.
 *
 * @param array<string, mixed> $attrs
 */
function spa_headless_flatten_blockstudio_attributes( array &$attrs ): void {
	if ( ! isset( $attrs['blockstudio'] ) || ! is_array( $attrs['blockstudio'] ) ) {
		return;
	}

	$nested = $attrs['blockstudio']['attributes'] ?? null;
	if ( ! is_array( $nested ) ) {
		return;
	}

	foreach ( $nested as $key => $value ) {
		if ( ! is_string( $key ) || array_key_exists( $key, $attrs ) ) {
			continue;
		}
		$attrs[ $key ] = $value;
	}
}

/**
 * @return list<array<string, mixed>>
 */
function spa_headless_blocks_from_content( string $content ): array {
	if ( '' === $content ) {
		return array();
	}

	return spa_headless_normalize_blocks( parse_blocks( $content ) );
}

/**
 * Register `blocks` on the core posts and pages REST resources.
 */
function spa_headless_register_blocks_field(): void {
	register_rest_field(
		spa_headless_post_types(),
		'blocks',
		array(
			'get_callback' => static function ( array $post ): array {
				$post_id = isset( $post['id'] ) ? (int) $post['id'] : 0;
				$content = get_post_field( 'post_content', $post_id );
				if ( ! is_string( $content ) ) {
					return array();
				}

				return spa_headless_blocks_from_content( $content );
			},
			'schema'       => array(
				'description' => 'Block tree from parse_blocks(): blockName, attrs, innerBlocks, innerHTML, innerContent.',
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'blockName'    => array(
							'type' => array( 'string', 'null' ),
						),
						'attrs'        => array(
							'type' => 'object',
						),
						'innerBlocks'  => array(
							'type' => 'array',
						),
						'innerHTML'    => array(
							'type' => 'string',
						),
						'innerContent' => array(
							'type'  => 'array',
							'items' => array(
								'type' => array( 'string', 'null' ),
							),
						),
					),
				),
			),
		)
	);
}
