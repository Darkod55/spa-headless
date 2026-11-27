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
