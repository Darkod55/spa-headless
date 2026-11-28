<?php
/**
 * Normalizer checks that do not need WordPress.
 *
 * Run from the repo root: php scripts/blocks-normalize-test.php
 *
 * @package Spa_Headless
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

/**
 * parse_blocks() is a WordPress function. The normalizer calls it only from
 * spa_headless_blocks_from_content(), which this test does not use.
 *
 * @param mixed $value
 */
function apply_filters( string $hook, $value ) {
	return $value;
}

require dirname( __DIR__ ) . '/wordpress/plugins/spa-headless-core/includes/blocks.php';

/**
 * @param bool   $condition
 * @param string $message
 */
function spa_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
}

$parsed = array(
	array(
		'blockName'    => null,
		'attrs'        => array(),
		'innerBlocks'  => array(),
		'innerHTML'    => "\n\n",
		'innerContent' => array( "\n\n" ),
	),
	array(
		'blockName'    => 'spa/section',
		'attrs'        => array( 'heading' => 'How we help' ),
		'innerHTML'    => '',
		'innerContent' => array( null ),
		'innerBlocks'  => array(
			array(
				'blockName'    => 'core/paragraph',
				'attrs'        => array(),
				'innerBlocks'  => array(),
				'innerHTML'    => '<p>Strategy second.</p>',
				'innerContent' => array( '<p>Strategy second.</p>' ),
			),
		),
	),
	array(
		'blockName'    => null,
		'attrs'        => array(),
		'innerBlocks'  => array(),
		'innerHTML'    => '<p>Classic HTML</p>',
		'innerContent' => array( '<p>Classic HTML</p>' ),
	),
);

$out = spa_headless_normalize_blocks( $parsed );

spa_assert( 2 === count( $out ), 'Expected whitespace-only blocks to be dropped and classic HTML kept.' );
spa_assert( 'spa/section' === $out[0]['blockName'], 'Section blockName was not preserved.' );
spa_assert( 'How we help' === $out[0]['attrs']['heading'], 'Section attrs were not preserved.' );
spa_assert( array( null ) === $out[0]['innerContent'], 'Null innerContent markers must be preserved.' );
spa_assert( 'core/paragraph' === $out[0]['innerBlocks'][0]['blockName'], 'Inner blocks must be normalized recursively.' );
spa_assert( '<p>Strategy second.</p>' === $out[0]['innerBlocks'][0]['innerHTML'], 'innerHTML must be preserved.' );
spa_assert( null === $out[1]['blockName'], 'Freeform HTML keeps a null blockName.' );
spa_assert( '<p>Classic HTML</p>' === $out[1]['innerHTML'], 'Freeform innerHTML must be preserved.' );
spa_assert( array( 'post', 'page' ) === spa_headless_post_types(), 'Default post types should be post and page.' );

$hero = spa_headless_normalize_blocks(
	array(
		array(
			'blockName'    => 'spa/hero-banner-main',
			'attrs'        => array(
				'background' => 'red',
				'image'      => array(
					'ID'  => 9,
					'url' => 'https://squarepeg-cms.local/wp-content/uploads/hero.jpg',
					'alt' => 'Team',
					'sizes' => array(
						'full' => array(
							'width'  => 1600,
							'height' => 900,
						),
					),
				),
			),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		),
	)
);
$image = $hero[0]['attrs']['image'];
spa_assert( is_array( $image ), 'Hero image should be normalized to an object.' );
spa_assert( 9 === $image['id'], 'Hero image id should come from ID.' );
spa_assert( 'https://squarepeg-cms.local/wp-content/uploads/hero.jpg' === $image['url'], 'Hero image url should be kept.' );
spa_assert( 'Team' === $image['alt'], 'Hero image alt should be kept.' );
spa_assert( 1600 === $image['width'] && 900 === $image['height'], 'Hero image size should come from sizes.full.' );

$empty = spa_headless_prepare_hero_image( null );
spa_assert( null === $empty, 'A missing hero image should be null.' );

$coloured = spa_headless_normalize_blocks(
	array(
		array(
			'blockName'    => 'core/heading',
			'attrs'        => array(
				'level'       => 2,
				'blockstudio' => array(
					'attributes' => array(
						'text_colour' => array(
							'value' => 'var(--color-red)',
							'name'  => 'Red',
							'slug'  => 'red',
						),
					),
				),
			),
			'innerBlocks'  => array(),
			'innerHTML'    => '<h2 class="wp-block-heading">Title</h2>',
			'innerContent' => array( '<h2 class="wp-block-heading">Title</h2>' ),
		),
	)
);
spa_assert( 'red' === $coloured[0]['attrs']['text_colour']['slug'], 'Extension attributes should be copied onto attrs.' );
spa_assert(
	'red' === $coloured[0]['attrs']['blockstudio']['attributes']['text_colour']['slug'],
	'The blockstudio.attributes nest should be kept.'
);

$nested_image = spa_headless_normalize_blocks(
	array(
		array(
			'blockName'    => 'spa/hero-banner-default',
			'attrs'        => array(
				'background'  => 'royal',
				'blockstudio' => array(
					'attributes' => array(
						'image' => array(
							'ID'     => 4,
							'url'    => 'https://squarepeg-cms.local/wp-content/uploads/nested.jpg',
							'alt'    => 'Nested',
							'width'  => 800,
							'height' => 600,
						),
					),
				),
			),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		),
	)
);
spa_assert( 4 === $nested_image[0]['attrs']['image']['id'], 'A hero image stored only under blockstudio.attributes should be normalized.' );
spa_assert( 800 === $nested_image[0]['attrs']['image']['width'], 'Nested hero image dimensions should survive.' );

$saved = spa_headless_repair_parsed_blocks(
	array(
		array(
			'blockName'    => 'spa/hero-banner-main',
			'attrs'        => array(
				'background'  => array(
					'value' => 'var(--color-navy)',
					'name'  => 'Navy',
					'slug'  => 'navy',
				),
				'image'       => array(
					'id'  => 44,
					'url' => 'https://squarepeg-cms.local/wp-content/uploads/hero.jpg',
				),
				'blockstudio' => array(
					'attributes' => array(
						'background' => array(
							'value' => 'var(--color-navy)',
							'name'  => 'Navy',
							'slug'  => 'navy',
						),
						'image'      => 44,
					),
				),
			),
			'innerBlocks'  => array(
				array(
					'blockName'    => 'core/heading',
					'attrs'        => array(
						'level'       => 1,
						'text_colour' => '',
						'blockstudio' => array(
							'attributes' => array(
								'text_colour' => array(
									'value' => 'var(--color-white)',
									'name'  => 'White',
									'slug'  => 'white',
								),
							),
						),
					),
					'innerBlocks'  => array(),
					'innerHTML'    => '<h1 class="wp-block-heading"></h1>',
					'innerContent' => array( '<h1 class="wp-block-heading"></h1>' ),
				),
				array(
					'blockName'    => 'core/paragraph',
					'attrs'        => array(
						'text_colour' => '',
						'blockstudio' => array(
							'attributes' => array(
								'text_colour' => '',
							),
						),
					),
					'innerBlocks'  => array(),
					'innerHTML'    => '<p></p>',
					'innerContent' => array( '<p></p>' ),
				),
			),
			'innerHTML'    => '',
			'innerContent' => array( null, null ),
		),
	)
);
spa_assert( 'spa/hero-banner-main' === $saved[0]['blockName'], 'Repair must keep the hero block.' );
spa_assert( 'navy' === $saved[0]['attrs']['background']['slug'], 'A real background object stays.' );
spa_assert( 44 === $saved[0]['attrs']['image']['id'], 'A saved image must not be replaced by the nested id alone.' );
spa_assert( 'white' === $saved[0]['innerBlocks'][0]['attrs']['text_colour']['slug'], 'An empty text_colour must lose to the nested colour object.' );
spa_assert( ! array_key_exists( 'text_colour', $saved[0]['innerBlocks'][1]['attrs'] ), 'An empty text_colour with no nested colour is removed.' );

$legacy = spa_headless_colour_object( 'navy' );
spa_assert( is_array( $legacy ) && 'navy' === $legacy['slug'] && 'var(--color-navy)' === $legacy['value'], 'A legacy slug string becomes a colour object.' );
spa_assert( null === spa_headless_colour_object( '' ), 'An empty string is not a colour.' );

fwrite( STDOUT, "blocks normalizer ok\n" );
