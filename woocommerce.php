<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="pagina">
	<section class="seccion">
		<div class="contenedor">
			<?php if ( is_shop() || is_product_taxonomy() ) : ?>
				<header class="seccion__cabecera rv">
					<h1 class="pagina__titulo"><?php woocommerce_page_title(); ?></h1>
				</header>
			<?php endif; ?>

			<div class="woocommerce-wrap rv">
				<?php woocommerce_content(); ?>
			</div>
		</div>
	</section>
</main>

<?php get_footer(); ?>
