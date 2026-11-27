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

fwrite( STDOUT, "blocks normalizer ok\n" );
