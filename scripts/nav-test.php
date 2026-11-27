<?php
/**
 * Menu URL and tree checks that do not need WordPress.
 *
 * @package Spa_Headless
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

require dirname( __DIR__ ) . '/wordpress/plugins/spa-headless-core/includes/routes.php';
require dirname( __DIR__ ) . '/wordpress/plugins/spa-headless-core/includes/nav.php';

/**
 * @param bool   $condition
 * @param string $message
 */
function spa_nav_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
}

$home = 'http://squarepeg-cms.local';

spa_nav_assert(
	'/about' === spa_headless_public_href( 'http://squarepeg-cms.local/about/', $home ),
	'Same-host menu URLs should become a relative path.'
);
spa_nav_assert(
	'/contact?enquiry=website-audit' === spa_headless_public_href( 'http://squarepeg-cms.local/contact/?enquiry=website-audit', $home ),
	'Query strings should survive the relative conversion.'
);
spa_nav_assert(
	'https://example.com/about' === spa_headless_public_href( 'https://example.com/about', $home ),
	'External URLs should stay absolute.'
);
spa_nav_assert(
	'/services#brand' === spa_headless_public_href( '/services/#brand', $home ),
	'Already-relative links should be normalized.'
);

$tree = spa_headless_menu_tree(
	array(
		array(
			'id'      => 1,
			'title'   => 'Home',
			'url'     => '/',
			'target'  => '',
			'classes' => array(),
			'parent'  => 0,
		),
		array(
			'id'      => 2,
			'title'   => 'Services',
			'url'     => '/services',
			'target'  => '',
			'classes' => array(),
			'parent'  => 0,
		),
		array(
			'id'      => 3,
			'title'   => 'Website design',
			'url'     => '/services',
			'target'  => '',
			'classes' => array(),
			'parent'  => 2,
		),
		array(
			'id'      => 4,
			'title'   => "Let's talk",
			'url'     => '/contact',
			'target'  => '',
			'classes' => array( 'cta' ),
			'parent'  => 0,
		),
	),
	'/services'
);

spa_nav_assert( 3 === count( $tree ), 'Top level should be Home, Services, and the CTA.' );
spa_nav_assert( 'Services' === $tree[1]['title'], 'Services should stay a parent.' );
spa_nav_assert( 1 === count( $tree[1]['children'] ), 'The website item should nest under Services.' );
spa_nav_assert( true === $tree[1]['current'], 'Services should be the current item on /services.' );
spa_nav_assert( false === $tree[0]['current'], 'Home should not be current on an inner page.' );
spa_nav_assert( true === $tree[1]['children'][0]['current'], 'A child with the same path is also current.' );

$blog = spa_headless_menu_tree(
	array(
		array(
			'id'      => 9,
			'title'   => 'Blog',
			'url'     => '/blog',
			'target'  => '',
			'classes' => array(),
			'parent'  => 0,
		),
	),
	'/blog/how-ai-search-is-changing-support-services'
);
spa_nav_assert( true === $blog[0]['currentAncestor'], 'A section item should be an ancestor of its posts.' );
spa_nav_assert( false === $blog[0]['current'], 'The section item is not the exact page.' );

$size = spa_headless_svg_dimensions( '<svg viewBox="0 0 819.62 211.85"></svg>' );
spa_nav_assert( 820 === $size['width'] && 212 === $size['height'], 'SVG viewBox should supply missing pixel size.' );

fwrite( STDOUT, "nav ok\n" );
