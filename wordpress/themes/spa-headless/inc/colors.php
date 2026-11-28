<?php
/**
 * Brand colour dataset for BlockStudio colour fields.
 *
 * Options are CSS variables. The hex values live in global.css and editor.css.
 *
 * @package Spa_Headless
 */

declare(strict_types=1);

if ( function_exists( 'add_filter' ) ) {
	add_filter( 'blockstudio/blocks/attributes/populate', 'spa_brand_colours_dataset' );
}

/**
 * Navy, red, royal, black, white. `value` is the CSS variable the editor stores;
 * `slug` is what templates and Astro turn into has-text-* / hero--bg-* classes.
 *
 * @return list<array{value: string, name: string, slug: string}>
 */
function spa_brand_colours(): array {
	return array(
		array(
			'value' => 'var(--color-navy)',
			'name'  => 'Navy',
			'slug'  => 'navy',
		),
		array(
			'value' => 'var(--color-red)',
			'name'  => 'Red',
			'slug'  => 'red',
		),
		array(
			'value' => 'var(--color-royal)',
			'name'  => 'Royal',
			'slug'  => 'royal',
		),
		array(
			'value' => 'var(--color-black)',
			'name'  => 'Black',
			'slug'  => 'black',
		),
		array(
			'value' => 'var(--color-white)',
			'name'  => 'White',
			'slug'  => 'white',
		),
	);
}

/**
 * @param mixed $options
 * @return array<string, mixed>
 */
function spa_brand_colours_dataset( $options ): array {
	if ( ! is_array( $options ) ) {
		$options = array();
	}

	$options['brandColours'] = spa_brand_colours();

	return $options;
}
