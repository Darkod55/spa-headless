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
			'value' => 'var(--color-primary)',
			'name'  => 'Primary',
			'slug'  => 'primary',
		),
		array(
			'value' => 'var(--color-secondary)',
			'name'  => 'Secondary',
			'slug'  => 'secondary',
		),
		array(
			'value' => 'var(--color-tertiary)',
			'name'  => 'Tertiary',
			'slug'  => 'tertiary',
		),
		array(
			'value' => 'var(--color-white)',
			'name'  => 'White',
			'slug'  => 'white',
		),
		array(
			'value' => 'var(--color-gray)',
			'name'  => 'Gray',
			'slug'  => 'gray',
		),
		array(
			'value' => 'var(--color-black)',
			'name'  => 'Black',
			'slug'  => 'black',
		),
		array(
			'value' => 'var(--color-secondary-deep)',
			'name'  => 'Secondary deep',
			'slug'  => 'secondary-deep',
		),
		array(
			'value' => 'var(--color-secondary-on-primary)',
			'name'  => 'Red on navy',
			'slug'  => 'secondary-on-primary',
		),
		array(
			'value' => 'var(--color-secondary-on-tertiary)',
			'name'  => 'Red on royal',
			'slug'  => 'secondary-on-tertiary',
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
