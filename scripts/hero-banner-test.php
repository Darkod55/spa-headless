<?php
/**
 * Hero banner class names. No WordPress.
 *
 * @package Spa_Headless
 */

declare(strict_types=1);

require dirname( __DIR__ ) . '/wordpress/themes/spa-headless/inc/hero-banner.php';

/**
 * @param bool   $condition
 * @param string $message
 */
function spa_hero_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
}

spa_hero_assert( 'hero--bg-navy' === spa_hero_banner_modifier( 'navy' ), 'Navy maps to hero--bg-navy.' );
spa_hero_assert( 'hero--bg-red' === spa_hero_banner_modifier( 'red' ), 'Red maps to hero--bg-red.' );
spa_hero_assert( 'hero--bg-royal' === spa_hero_banner_modifier( 'royal' ), 'Royal maps to hero--bg-royal.' );
spa_hero_assert( 'hero--bg-black' === spa_hero_banner_modifier( 'black' ), 'Black maps to hero--bg-black.' );
spa_hero_assert( 'hero--bg-white' === spa_hero_banner_modifier( 'white' ), 'White maps to hero--bg-white.' );
spa_hero_assert( 'hero--bg-navy' === spa_hero_banner_modifier( 'lime' ), 'Unknown backgrounds fall back to navy.' );
spa_hero_assert( 'hero--bg-navy' === spa_hero_banner_modifier( null ), 'A missing background falls back to navy.' );
spa_hero_assert( 'hero--main' === spa_hero_banner_variant_class( 'main' ), 'Main uses hero--main.' );
spa_hero_assert( 'hero--default' === spa_hero_banner_variant_class( 'default' ), 'Default uses hero--default.' );

fwrite( STDOUT, "hero banner ok\n" );
