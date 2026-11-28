<?php
/**
 * Hero banner class names. No WordPress calls, so tests can load this file.
 *
 * @package Spa_Headless
 */

declare(strict_types=1);

/**
 * Background value → modifier class. Unknown values use navy.
 */
function spa_hero_banner_modifier( mixed $background ): string {
	$map = array(
		'navy'  => 'hero--bg-navy',
		'red'   => 'hero--bg-red',
		'royal' => 'hero--bg-royal',
		'black' => 'hero--bg-black',
		'white' => 'hero--bg-white',
	);
	$key = is_string( $background ) ? $background : '';

	return $map[ $key ] ?? 'hero--bg-navy';
}

/**
 * @return 'hero--main'|'hero--default'
 */
function spa_hero_banner_variant_class( string $variant ): string {
	return 'main' === $variant ? 'hero--main' : 'hero--default';
}
