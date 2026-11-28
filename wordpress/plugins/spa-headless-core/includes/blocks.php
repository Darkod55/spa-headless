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
 * A colour was cleared, or never chosen. "" is what the colour control writes
 * when clearable; Gutenberg then rejects it if the attribute type is object.
 *
 * @param mixed $value
 */
function spa_headless_value_is_empty( mixed $value ): bool {
	if ( null === $value || false === $value ) {
		return true;
	}
	if ( is_string( $value ) ) {
		return '' === trim( $value );
	}
	if ( ! is_array( $value ) || array() === $value ) {
		return ! is_array( $value ) ? false : true;
	}

	$slug  = $value['slug'] ?? null;
	$color = $value['value'] ?? null;
	$has   = ( is_string( $slug ) && '' !== $slug ) || ( is_string( $color ) && '' !== $color );
	if ( $has ) {
		return false;
	}

	return array_key_exists( 'slug', $value ) || array_key_exists( 'value', $value ) || array_key_exists( 'name', $value );
}

/**
 * @param mixed $value
 * @return array{value: string, name: string, slug: string}|null
 */
function spa_headless_colour_object( mixed $value ): ?array {
	$names = array(
		'navy'  => 'Navy',
		'red'   => 'Red',
		'royal' => 'Royal',
		'black' => 'Black',
		'white' => 'White',
	);

	if ( is_array( $value ) && isset( $value['slug'] ) && is_string( $value['slug'] ) && isset( $names[ $value['slug'] ] ) ) {
		$slug = $value['slug'];
		return array(
			'value' => isset( $value['value'] ) && is_string( $value['value'] ) && '' !== $value['value'] ? $value['value'] : 'var(--color-' . $slug . ')',
			'name'  => isset( $value['name'] ) && is_string( $value['name'] ) && '' !== $value['name'] ? $value['name'] : $names[ $slug ],
			'slug'  => $slug,
		);
	}

	if ( ! is_string( $value ) ) {
		return null;
	}

	$slug = trim( $value );
	if ( preg_match( '/^var\(--color-([a-z]+)\)$/', $slug, $match ) ) {
		$slug = $match[1];
	}
	if ( ! isset( $names[ $slug ] ) ) {
		return null;
	}

	return array(
		'value' => 'var(--color-' . $slug . ')',
		'name'  => $names[ $slug ],
		'slug'  => $slug,
	);
}

/**
 * Copy BlockStudio extension values onto the attribute bag.
 *
 * An empty top-level value (the colour control writes "") loses to the
 * object stored at blockstudio.attributes. The nest is kept. Empty colour
 * attributes are removed so the editor is not given a string where it
 * expects an object.
 *
 * @param array<string, mixed> $attrs
 */
function spa_headless_flatten_blockstudio_attributes( array &$attrs ): void {
	$nested = null;
	if ( isset( $attrs['blockstudio'] ) && is_array( $attrs['blockstudio'] ) && isset( $attrs['blockstudio']['attributes'] ) && is_array( $attrs['blockstudio']['attributes'] ) ) {
		$nested = $attrs['blockstudio']['attributes'];
	}

	if ( is_array( $nested ) ) {
		foreach ( $nested as $key => $value ) {
			if ( ! is_string( $key ) ) {
				continue;
			}
			if ( array_key_exists( $key, $attrs ) && ! spa_headless_value_is_empty( $attrs[ $key ] ) ) {
				continue;
			}
			if ( spa_headless_value_is_empty( $value ) ) {
				continue;
			}
			$attrs[ $key ] = $value;
		}
	}

	foreach ( array( 'text_colour', 'background' ) as $key ) {
		if ( ! array_key_exists( $key, $attrs ) ) {
			continue;
		}
		$object = spa_headless_colour_object( $attrs[ $key ] );
		if ( null !== $object ) {
			$attrs[ $key ] = $object;
			continue;
		}
		if ( spa_headless_value_is_empty( $attrs[ $key ] ) ) {
			unset( $attrs[ $key ] );
		}
	}
}

/**
 * Repair parsed blocks so a saved "" colour does not hide the real object.
 *
 * @param array<int, mixed> $blocks
 * @return array<int, mixed>
 */
function spa_headless_repair_parsed_blocks( array $blocks ): array {
	$repaired = array();

	foreach ( $blocks as $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}

		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		spa_headless_flatten_blockstudio_attributes( $attrs );
		$block['attrs'] = $attrs;

		if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			$block['innerBlocks'] = spa_headless_repair_parsed_blocks( $block['innerBlocks'] );
		}

		$repaired[] = $block;
	}

	return $repaired;
}

/**
 * Rewrite post_content the block editor will parse. The database is not changed.
 */
function spa_headless_repair_post_content( string $content ): string {
	if ( '' === $content || ! function_exists( 'parse_blocks' ) || ! function_exists( 'serialize_blocks' ) ) {
		return $content;
	}
	if ( ! str_contains( $content, 'text_colour' ) && ! str_contains( $content, 'blockstudio' ) ) {
		return $content;
	}

	$repaired = serialize_blocks( spa_headless_repair_parsed_blocks( parse_blocks( $content ) ) );

	return is_string( $repaired ) && '' !== $repaired ? $repaired : $content;
}

/**
 * The editor reads content.raw, not the spa/v1 payload. Give it the colour
 * object so BlockStudio can read .slug and the hero stays in the canvas.
 *
 * @param WP_REST_Response|mixed $response
 * @param WP_Post|mixed          $post
 * @param WP_REST_Request|mixed  $request
 * @return mixed
 */
function spa_headless_rest_repair_editor_content( $response, $post, $request ) {
	unset( $post );
	if ( ! $response instanceof WP_REST_Response || ! $request instanceof WP_REST_Request ) {
		return $response;
	}
	if ( 'edit' !== $request->get_param( 'context' ) ) {
		return $response;
	}

	$data = $response->get_data();
	if ( ! is_array( $data ) || ! isset( $data['content']['raw'] ) || ! is_string( $data['content']['raw'] ) ) {
		return $response;
	}

	$repaired = spa_headless_repair_post_content( $data['content']['raw'] );
	if ( $repaired !== $data['content']['raw'] ) {
		$data['content']['raw'] = $repaired;
		$response->set_data( $data );
	}

	return $response;
}

/**
 * Accept both the legacy string and the colour object. An object-only schema
 * drops "" during parse; a string-only schema drops the saved object.
 *
 * @param array<string, mixed> $args
 * @return array<string, mixed>
 */
function spa_headless_widen_colour_attribute_args( array $args ): array {
	if ( ! isset( $args['attributes'] ) || ! is_array( $args['attributes'] ) ) {
		$args['attributes'] = array();
	}

	foreach ( array( 'text_colour', 'background' ) as $key ) {
		if ( ! isset( $args['attributes'][ $key ] ) || ! is_array( $args['attributes'][ $key ] ) ) {
			continue;
		}
		$args['attributes'][ $key ]['type'] = array( 'string', 'object' );
	}

	return $args;
}

/**
 * @param array<string, mixed> $args
 * @return array<string, mixed>
 */
function spa_headless_register_block_type_args( array $args, string $name ): array {
	$colour_blocks = array( 'core/heading', 'core/paragraph', 'spa/hero-banner-main', 'spa/hero-banner-default' );
	if ( ! in_array( $name, $colour_blocks, true ) ) {
		return $args;
	}

	return spa_headless_widen_colour_attribute_args( $args );
}

/**
 * BlockStudio adds extension attributes as it registers the block. Widen the
 * type again after that, and add text_colour if the extension stored it only
 * inside blockstudio.attributes.
 */
function spa_headless_widen_registered_colour_attributes(): void {
	if ( ! class_exists( 'WP_Block_Type_Registry' ) ) {
		return;
	}

	$registry = WP_Block_Type_Registry::get_instance();
	foreach ( array( 'core/heading', 'core/paragraph', 'spa/hero-banner-main', 'spa/hero-banner-default' ) as $name ) {
		$block = $registry->get_registered( $name );
		if ( ! $block instanceof WP_Block_Type ) {
			continue;
		}
		if ( ! is_array( $block->attributes ) ) {
			$block->attributes = array();
		}
		if ( ( 'core/heading' === $name || 'core/paragraph' === $name ) && ! isset( $block->attributes['text_colour'] ) ) {
			$block->attributes['text_colour'] = array(
				'type' => array( 'string', 'object' ),
			);
		}
		foreach ( array( 'text_colour', 'background' ) as $key ) {
			if ( ! isset( $block->attributes[ $key ] ) || ! is_array( $block->attributes[ $key ] ) ) {
				continue;
			}
			$block->attributes[ $key ]['type'] = array( 'string', 'object' );
		}
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
