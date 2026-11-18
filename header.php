<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$d = chir_datos();
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#BFE0F1">
	<meta name="description" content="<?php echo esc_attr( $d['descripcion'] ); ?>">
	<script>document.documentElement.className += ' js';</script>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="saltar" href="#carta">Ir a la carta</a>

<header class="cabecera">
	<div class="cabecera__pill glass">
		<a class="marca" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( $d['nombre'] ); ?></a>
		<nav class="menu" aria-label="Principal">
			<a href="<?php echo esc_url( home_url( '/#carta' ) ); ?>">Carta</a>
			<a href="<?php echo esc_url( home_url( '/#terraza' ) ); ?>">Terraza</a>
			<a href="<?php echo esc_url( home_url( '/#reservar' ) ); ?>">Horario</a>
			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Tienda</a>
			<?php endif; ?>
		</nav>
		<a class="boton boton--solido boton--chico" href="<?php echo esc_url( home_url( '/#reservar' ) ); ?>">Reservar</a>
	</div>
</header>
