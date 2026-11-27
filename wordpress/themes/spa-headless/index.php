<?php
/**
 * Editor and WordPress preview shell.
 *
 * The public site is the Astro app (http://localhost:4321 in local dev).
 * This template renders saved blocks so the block editor preview and a
 * direct visit to the CMS still show the content.
 *
 * @package Spa_Headless
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$spa_front = defined( 'SPA_FRONTEND_URL' ) ? (string) SPA_FRONTEND_URL : 'http://localhost:4321';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<p class="spa-cms-note">
	<?php esc_html_e( 'WordPress preview. The public site renders in Astro.', 'spa-headless' ); ?>
	<a href="<?php echo esc_url( $spa_front ); ?>"><?php echo esc_html( $spa_front ); ?></a>
</p>

<main>
<?php
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		the_content();
	}
}
?>
</main>

<?php wp_footer(); ?>
</body>
</html>
