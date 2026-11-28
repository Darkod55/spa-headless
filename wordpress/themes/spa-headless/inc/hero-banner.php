<?php
/**
 * Hero banner class names. No WordPress calls, so tests can load this file.
 *
 * @package Spa_Headless
 */

declare(strict_types=1);

/**
 * Slug from a legacy string ("navy" or "var(--color-navy)") or a colour object.
 */
function spa_hero_banner_slug( mixed $background ): string {
	if ( is_string( $background ) ) {
		$background = trim( $background );
		if ( preg_match( '/^var\(--color-([a-z]+)\)$/', $background, $match ) ) {
			return $match[1];
		}

		return $background;
	}

	if ( is_array( $background ) && isset( $background['slug'] ) && is_string( $background['slug'] ) ) {
		return $background['slug'];
	}

	return '';
}

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
	$key = spa_hero_banner_slug( $background );

	return $map[ $key ] ?? 'hero--bg-navy';
}

/**
 * @return 'hero--main'|'hero--default'
 */
function spa_hero_banner_variant_class( string $variant ): string {
	return 'main' === $variant ? 'hero--main' : 'hero--default';
}
