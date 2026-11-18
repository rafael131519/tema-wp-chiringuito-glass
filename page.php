<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="pagina">
	<section class="seccion">
		<div class="contenedor">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<header class="seccion__cabecera rv">
					<h1 class="pagina__titulo"><?php the_title(); ?></h1>
				</header>
				<div class="pagina__contenido glass rv">
					<?php the_content(); ?>
				</div>
				<?php
			endwhile;
			?>
		</div>
	</section>
</main>

<?php get_footer(); ?>
