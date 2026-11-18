<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$d        = chir_datos();
$img_uri  = get_template_directory_uri() . '/assets/img/';
$img_dir  = get_template_directory() . '/assets/img/';
$wa_base  = 'https://wa.me/' . $d['whatsapp'];
$wa_hola  = $wa_base . '?text=' . rawurlencode( 'Hola, quiero reservar mesa en ' . $d['nombre'] . '.' );
$reserva  = $d['reserva_url'] ? $d['reserva_url'] : $wa_hola;

// Horario en minutos para el aviso "abierto ahora" (lo calcula main.js).
$horario_js = array();
foreach ( $d['horario'] as $num => $h ) {
	if ( ! empty( $h['cerrado'] ) ) {
		$horario_js[ $num ] = null;
		continue;
	}
	list( $ah, $am ) = array_map( 'intval', explode( ':', $h['abre'] ) );
	list( $ch, $cm ) = array_map( 'intval', explode( ':', $h['cierra'] ) );
	$horario_js[ $num ] = array( $ah * 60 + $am, $ch * 60 + $cm );
}
$dias_js = array();
foreach ( $d['horario'] as $num => $h ) {
	$dias_js[ $num ] = $h['dia'];
}

// Franjas de hora para el formulario.
$franjas = array();
list( $fh, $fm ) = array_map( 'intval', explode( ':', $d['reserva_desde'] ) );
list( $th, $tm ) = array_map( 'intval', explode( ':', $d['reserva_hasta'] ) );
for ( $m = $fh * 60 + $fm; $m <= $th * 60 + $tm; $m += 30 ) {
	$franjas[] = sprintf( '%02d:%02d', intdiv( $m, 60 ), $m % 60 );
}

function chir_hora( $h ) {
	return '24:00' === $h ? '00:00' : $h;
}
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
		<a class="marca" href="#inicio"><?php echo esc_html( $d['nombre'] ); ?></a>
		<nav class="menu" aria-label="Principal">
			<a href="#carta">Carta</a>
			<a href="#terraza">Terraza</a>
			<a href="#reservar">Horario</a>
		</nav>
		<a class="boton boton--solido boton--chico" href="#reservar">Reservar</a>
	</div>
</header>

<main id="inicio">

	<!-- ============ HERO ============ -->
	<section class="hero">
		<?php echo chir_palmera( false ); // phpcs:ignore ?>
		<?php echo chir_palmera( true ); // phpcs:ignore ?>

		<div class="hero__contenido">
			<div class="hero__texto">
				<h1><?php echo esc_html( $d['eslogan'] ); ?></h1>
				<p class="hero__desc"><?php echo esc_html( $d['descripcion'] ); ?></p>
				<div class="hero__botones">
					<a class="boton boton--solido" href="#reservar">Reservar mesa</a>
					<a class="boton boton--glass" href="#carta">Ver la carta</a>
				</div>
			</div>

			<aside class="hoy glass" aria-label="Horario de hoy"
				data-horario="<?php echo esc_attr( wp_json_encode( $horario_js ) ); ?>"
				data-dias="<?php echo esc_attr( wp_json_encode( $dias_js ) ); ?>">
				<p class="hoy__estado" id="estado"><span class="punto" aria-hidden="true"></span><span id="estado-texto">Consulta el horario</span></p>
				<p class="hoy__linea"><?php echo esc_html( $d['direccion'] ); ?></p>
				<a class="hoy__enlace" href="<?php echo esc_url( $d['mapa_url'] ); ?>" target="_blank" rel="noopener">Cómo llegar</a>
			</aside>
		</div>

		<div class="olas" aria-hidden="true">
			<svg class="ola ola--1" viewBox="0 0 2400 200" preserveAspectRatio="none" focusable="false">
				<defs>
					<linearGradient id="g-ola1" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#2B7FA6" stop-opacity=".42"/><stop offset="1" stop-color="#2B7FA6" stop-opacity="0"/></linearGradient>
				</defs>
				<path d="<?php echo esc_attr( chir_ola_trazo( 70, 30 ) ); ?> L2400,200 L0,200 Z" fill="url(#g-ola1)"/>
			</svg>
			<svg class="ola ola--2" viewBox="0 0 2400 200" preserveAspectRatio="none" focusable="false">
				<defs>
					<linearGradient id="g-ola2" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#52B5BE" stop-opacity=".55"/><stop offset="1" stop-color="#52B5BE" stop-opacity="0"/></linearGradient>
				</defs>
				<path d="<?php echo esc_attr( chir_ola_trazo( 90, 46 ) ); ?> L2400,200 L0,200 Z" fill="url(#g-ola2)"/>
				<path d="<?php echo esc_attr( chir_ola_trazo( 90, 46 ) ); ?>" fill="none" stroke="#F8FCFB" stroke-opacity=".55" stroke-width="3"/>
			</svg>
			<svg class="ola ola--3" viewBox="0 0 2400 200" preserveAspectRatio="none" focusable="false">
				<defs>
					<linearGradient id="g-ola3" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#F8FCFB" stop-opacity=".78"/><stop offset="1" stop-color="#F8FCFB" stop-opacity="0"/></linearGradient>
				</defs>
				<path d="<?php echo esc_attr( chir_ola_trazo( 110, 62 ) ); ?> L2400,200 L0,200 Z" fill="url(#g-ola3)"/>
				<path d="<?php echo esc_attr( chir_ola_trazo( 110, 62 ) ); ?>" fill="none" stroke="#FFFFFF" stroke-width="7" stroke-linecap="round" stroke-dasharray="46 16 8 16"/>
			</svg>
		</div>
	</section>

	<!-- ============ CARTA ============ -->
	<section class="seccion" id="carta">
		<div class="contenedor">
			<header class="seccion__cabecera rv">
				<h2>Carta</h2>
				<p><?php echo esc_html( $d['carta_nota'] ); ?></p>
			</header>

			<div class="carta">
				<?php foreach ( $d['carta'] as $categoria => $platos ) : ?>
					<article class="carta__panel glass rv">
						<h3><?php echo esc_html( $categoria ); ?></h3>
						<ul>
							<?php foreach ( $platos as $plato ) : ?>
								<li class="plato">
									<span class="plato__n"><?php echo esc_html( $plato[0] ); ?></span>
									<span class="plato__linea" aria-hidden="true"></span>
									<span class="plato__p"><?php echo esc_html( $plato[1] ); ?></span>
									<?php if ( ! empty( $plato[2] ) ) : ?>
										<small class="plato__nota"><?php echo esc_html( $plato[2] ); ?></small>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- ============ TERRAZA / GALERÍA ============ -->
	<section class="seccion" id="terraza">
		<div class="contenedor">
			<header class="seccion__cabecera rv">
				<h2>La terraza</h2>
				<p>Mesas cubiertas junto al paseo y mesas sobre la arena. Se reparten por orden de llegada, salvo con reserva.</p>
			</header>

			<div class="galeria">
				<?php foreach ( $d['galeria'] as $i => $pie ) :
					$n    = $i + 1;
					$file = "galeria-{$n}.jpg";
					?>
					<figure class="foto foto--<?php echo (int) $n; ?> rv">
						<?php if ( file_exists( $img_dir . $file ) ) : ?>
							<img src="<?php echo esc_url( $img_uri . $file ); ?>" alt="<?php echo esc_attr( $pie ); ?>" loading="lazy" width="900" height="700">
						<?php else : ?>
							<span class="foto__relleno" role="img" aria-label="<?php echo esc_attr( $pie ); ?>"></span>
						<?php endif; ?>
						<figcaption class="glass"><?php echo esc_html( $pie ); ?></figcaption>
					</figure>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- ============ RESERVA ============ -->
	<section class="seccion seccion--ultima" id="reservar">
		<div class="contenedor">
			<header class="seccion__cabecera rv">
				<h2>Reservar mesa</h2>
				<p>Respondemos por WhatsApp en menos de una hora dentro del horario de apertura.</p>
			</header>

			<div class="reserva">
				<div class="reserva__info glass rv">
					<h3>Horario</h3>
					<table class="tabla-horario">
						<tbody>
							<?php foreach ( $d['horario'] as $num => $h ) : ?>
								<tr data-dia="<?php echo (int) $num; ?>">
									<th scope="row"><?php echo esc_html( $h['dia'] ); ?></th>
									<td>
										<?php
										echo ! empty( $h['cerrado'] )
											? 'Cerrado'
											: esc_html( chir_hora( $h['abre'] ) . ' – ' . chir_hora( $h['cierra'] ) );
										?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<p class="contacto">
						<?php echo esc_html( $d['direccion'] ); ?><br>
						<a href="tel:+<?php echo esc_attr( $d['whatsapp'] ); ?>"><?php echo esc_html( $d['telefono'] ); ?></a><br>
						<a href="mailto:<?php echo esc_attr( $d['email'] ); ?>"><?php echo esc_html( $d['email'] ); ?></a>
					</p>
				</div>

				<div class="reserva__form glass rv">
					<?php if ( $d['reserva_url'] ) : ?>
						<h3>Reserva online</h3>
						<p>La reserva se gestiona en la web de reservas del local.</p>
						<a class="boton boton--solido" href="<?php echo esc_url( $d['reserva_url'] ); ?>" target="_blank" rel="noopener">Ir a reservar</a>
					<?php else : ?>
						<h3>Datos de la reserva</h3>
						<form id="form-reserva" data-wa="<?php echo esc_url( $wa_base ); ?>" data-nombre="<?php echo esc_attr( $d['nombre'] ); ?>">
							<div class="campo">
								<label for="r-nombre">Nombre</label>
								<input id="r-nombre" name="nombre" type="text" autocomplete="given-name" required>
							</div>
							<div class="campos-2">
								<div class="campo">
									<label for="r-personas">Personas</label>
									<input id="r-personas" name="personas" type="number" min="1" max="20" value="2" inputmode="numeric" required>
								</div>
								<div class="campo">
									<label for="r-fecha">Fecha</label>
									<input id="r-fecha" name="fecha" type="date" required>
								</div>
							</div>
							<div class="campos-2">
								<div class="campo">
									<label for="r-hora">Hora</label>
									<select id="r-hora" name="hora" required>
										<?php foreach ( $franjas as $f ) : ?>
											<option value="<?php echo esc_attr( $f ); ?>"><?php echo esc_html( $f ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="campo">
									<label for="r-zona">Zona</label>
									<select id="r-zona" name="zona">
										<option>Terraza</option>
										<option>Sobre la arena</option>
										<option>Me da igual</option>
									</select>
								</div>
							</div>
							<button class="boton boton--solido boton--ancho" type="submit">Enviar por WhatsApp</button>
							<p class="form__nota">Se abre WhatsApp con el mensaje escrito. La reserva queda confirmada cuando el local responde.</p>
						</form>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
</main>

<footer class="pie">
	<div class="contenedor pie__fila glass">
		<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $d['nombre'] ); ?></p>
		<p><a href="https://instagram.com/<?php echo esc_attr( $d['instagram'] ); ?>" target="_blank" rel="noopener">@<?php echo esc_html( $d['instagram'] ); ?></a></p>
		<p>Web de demostración por KizaryDev</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
