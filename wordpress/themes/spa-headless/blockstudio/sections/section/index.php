<?php
/**
 * Section block with a single InnerBlocks region.
 *
 * WordPress allows one InnerBlocks per block. Core blocks (paragraph,
 * heading, image, list, buttons, group, columns) are the usual children.
 * spa/section is omitted so sections do not nest themselves.
 *
 * @var array<string, mixed> $a
 * @package Spa_Headless
 */

list( $section_classes, $container_classes ) = spa_section_classes( $a );

$allowed = array(
	'core/heading',
	'core/paragraph',
	'core/image',
	'core/list',
	'core/buttons',
	'core/group',
	'core/columns',
	'spa/hero',
	'spa/card-grid',
);

$template = array(
	array(
		'core/heading',
		array(
			'level'       => 2,
			'placeholder' => 'Section heading',
		),
	),
	array(
		'core/paragraph',
		array(
			'placeholder' => 'Section content',
		),
	),
);
?>
<section useBlockProps class="<?php echo esc_attr( $section_classes ); ?>">
	<div class="<?php echo esc_attr( $container_classes ); ?>">
		<?php if ( ! empty( $a['heading'] ) && is_string( $a['heading'] ) ) : ?>
			<h2><?php echo esc_html( $a['heading'] ); ?></h2>
		<?php endif; ?>

		<?php if ( ! empty( $a['intro'] ) && is_string( $a['intro'] ) ) : ?>
			<p class="spa-lead"><?php echo esc_html( $a['intro'] ); ?></p>
		<?php endif; ?>

		<InnerBlocks
			allowedBlocks="<?php echo spa_json_attr( $allowed ); ?>"
			template="<?php echo spa_json_attr( $template ); ?>"
		/>
	</div>
</section>
