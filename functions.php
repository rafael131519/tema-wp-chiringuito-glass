<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Datos del negocio (inc/datos.php), cargados una sola vez. */
function chir_datos() {
	static $datos = null;
	if ( null === $datos ) {
		$datos = require get_template_directory() . '/inc/datos.php';
	}
	return $datos;
}

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'html5', array( 'style', 'script', 'search-form' ) );

	add_theme_support( 'woocommerce', array(
		'thumbnail_image_width' => 400,
		'single_image_width'    => 700,
		'product_grid'          => array(
			'default_rows'    => 3,
			'min_rows'        => 1,
			'max_rows'        => 8,
			'default_columns' => 3,
			'min_columns'     => 1,
			'max_columns'     => 4,
		),
	) );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
} );

// Usamos el css propio del tema para la tienda; se desactiva la hoja de estilos de WooCommerce.
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

// El título de la tienda lo pinta woocommerce.php (consistente con el resto de páginas); evita el duplicado.
add_filter( 'woocommerce_show_page_title', '__return_false' );

add_action( 'wp_enqueue_scripts', function () {
	$dir = get_template_directory();
	$uri = get_template_directory_uri();

	// Tipografías. En producción conviene alojarlas en el propio servidor (RGPD): ver README.
	wp_enqueue_style(
		'chir-fuentes',
		'https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wdth,wght@12..96,75..100,400..800&family=Figtree:wght@400;500;600&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'chir-main', $uri . '/assets/css/main.css', array( 'chir-fuentes' ), filemtime( $dir . '/assets/css/main.css' ) );
	wp_enqueue_script( 'chir-main', $uri . '/assets/js/main.js', array(), filemtime( $dir . '/assets/js/main.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );

	// Datos que main.js necesita para enviar la reserva: URL de admin-ajax.php y nonce de seguridad.
	wp_localize_script( 'chir-main', 'CHIR', array(
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'chir_reserva' ),
	) );
} );

require get_template_directory() . '/inc/reservas.php';

/** Quita lo que WordPress añade por defecto y no hace falta en una one-page. */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

/**
 * Ola animada en SVG: devuelve el trazo de una onda que se repite 4 veces (2400 de ancho),
 * de forma que desplazar el SVG un 50 % deja un bucle sin salto.
 */
function chir_ola_trazo( $y, $amplitud ) {
	$p = "M0,{$y} q150,-{$amplitud} 300,0";
	for ( $i = 0; $i < 7; $i++ ) {
		$p .= ' t300,0';
	}
	return $p;
}

/**
 * Palmera en silueta: un abanico de frondas generadas con una curva de Bézier y hojas a ambos lados.
 * $espejo = true la dibuja con la raíz en el borde derecho (para la esquina opuesta).
 */
function chir_palmera( $espejo = false ) {
	$dir    = $espejo ? -1 : 1;
	$rotas  = array( 4, 26, 50, 74 );
	$largos = array( 1.0, 0.92, 0.82, 0.66 );
	$salida = '';

	foreach ( $rotas as $k => $grados ) {
		$esc = $largos[ $k ];
		$rad = deg2rad( $grados );
		$cos = cos( $rad );
		$sin = sin( $rad );

		// Curva de la nervadura (antes de rotar): arco hacia arriba que cae.
		$p0 = array( 0, 0 );
		$p1 = array( 110 * $esc, -62 * $esc );
		$p2 = array( 250 * $esc, 14 * $esc );

		$hojas = '';
		$n     = 15;
		for ( $i = 1; $i <= $n; $i++ ) {
			$t  = $i / ( $n + 1 );
			$mt = 1 - $t;
			$x  = $mt * $mt * $p0[0] + 2 * $mt * $t * $p1[0] + $t * $t * $p2[0];
			$y  = $mt * $mt * $p0[1] + 2 * $mt * $t * $p1[1] + $t * $t * $p2[1];
			$tx = 2 * $mt * ( $p1[0] - $p0[0] ) + 2 * $t * ( $p2[0] - $p1[0] );
			$ty = 2 * $mt * ( $p1[1] - $p0[1] ) + 2 * $t * ( $p2[1] - $p1[1] );
			$an = atan2( $ty, $tx );
			$lg = 74 * $esc * ( 1 - 0.62 * $t );

			foreach ( array( 1, -1 ) as $lado ) {
				$a  = $an + $lado * deg2rad( 58 );
				$ex = $x + $lg * cos( $a );
				$ey = $y + $lg * sin( $a );
				$mx = ( $x + $ex ) / 2;
				$my = ( $y + $ey ) / 2;
				$px = -sin( $a ) * 5;
				$py = cos( $a ) * 5;
				$hojas .= sprintf(
					'M%.1f %.1f Q%.1f %.1f %.1f %.1f Q%.1f %.1f %.1f %.1f',
					$x, $y, $mx + $px, $my + $py, $ex, $ey, $mx - $px, $my - $py, $x, $y
				);
			}
		}

		$nerv = sprintf( 'M0 0 Q%.1f %.1f %.1f %.1f', $p1[0], $p1[1], $p2[0], $p2[1] );

		// Rotación y espejo aplicados con una matriz sobre el grupo.
		$m = sprintf( 'matrix(%.4f %.4f %.4f %.4f 0 0)', $dir * $cos, $sin, -$dir * $sin, $cos );
		$salida .= '<g transform="' . $m . '"><path d="' . $nerv . '" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"/><path d="' . $hojas . '" fill="currentColor"/></g>';
	}

	$vb = $espejo ? '-300 -90 300 400' : '0 -90 300 400';
	return '<svg class="palma' . ( $espejo ? ' palma--der' : '' ) . '" viewBox="' . $vb . '" aria-hidden="true" focusable="false">' . $salida . '</svg>';
}
