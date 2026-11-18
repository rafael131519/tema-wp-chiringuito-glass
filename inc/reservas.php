<?php
/**
 * RESERVAS — tabla MySQL, recepción del formulario (AJAX) y panel «Reservas» en el admin.
 *
 * Flujo:
 *   formulario (main.js) → admin-ajax.php?action=chir_reserva → chir_reserva_recibir() → INSERT en wp_reservas
 *   admin → menú «Reservas» → chir_reservas_pagina() → lista, filtros, confirmar / cancelar / eliminar
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CHIR_RESERVAS_DB_VERSION = '1';
const CHIR_ZONAS               = array( 'Terraza', 'Sobre la arena', 'Me da igual' );
const CHIR_ESTADOS             = array( 'pendiente', 'confirmada', 'cancelada' );

/** Nombre real de la tabla con el prefijo de esta instalación (wp_reservas). */
function chir_tabla() {
	global $wpdb;
	return $wpdb->prefix . 'reservas';
}

/* ------------------------------------------------------------------ */
/* 1. Tabla                                                            */
/* ------------------------------------------------------------------ */

function chir_reservas_instalar() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$tabla   = chir_tabla();
	$charset = $wpdb->get_charset_collate();

	// dbDelta es exigente con el formato: dos espacios tras PRIMARY KEY, un campo por línea.
	$sql = "CREATE TABLE {$tabla} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		nombre VARCHAR(80) NOT NULL,
		telefono VARCHAR(20) NOT NULL,
		personas TINYINT(3) UNSIGNED NOT NULL,
		fecha DATE NOT NULL,
		hora TIME NOT NULL,
		zona VARCHAR(20) NOT NULL DEFAULT 'Me da igual',
		estado VARCHAR(12) NOT NULL DEFAULT 'pendiente',
		creada DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY fecha (fecha),
		KEY estado (estado)
	) {$charset};";

	dbDelta( $sql );
	update_option( 'chir_reservas_db', CHIR_RESERVAS_DB_VERSION );
}
// Al activar el tema, y en cada carga si la versión guardada no coincide (cubre el tema ya activo).
add_action( 'after_switch_theme', 'chir_reservas_instalar' );
add_action( 'init', function () {
	if ( get_option( 'chir_reservas_db' ) !== CHIR_RESERVAS_DB_VERSION ) {
		chir_reservas_instalar();
	}
} );

/* ------------------------------------------------------------------ */
/* 2. Recepción del formulario                                          */
/* ------------------------------------------------------------------ */

/** Franjas válidas (las mismas que pinta el selector de hora). */
function chir_franjas_validas() {
	$d = chir_datos();
	list( $fh, $fm ) = array_map( 'intval', explode( ':', $d['reserva_desde'] ) );
	list( $th, $tm ) = array_map( 'intval', explode( ':', $d['reserva_hasta'] ) );
	$f = array();
	for ( $m = $fh * 60 + $fm; $m <= $th * 60 + $tm; $m += 30 ) {
		$f[] = sprintf( '%02d:%02d', intdiv( $m, 60 ), $m % 60 );
	}
	return $f;
}

function chir_reserva_error( $mensaje, $codigo = 422 ) {
	wp_send_json_error( array( 'mensaje' => $mensaje ), $codigo );
}

function chir_reserva_recibir() {
	// 1) Nonce: comprueba que el envío sale de esta web y no de un script externo.
	if ( ! check_ajax_referer( 'chir_reserva', 'nonce', false ) ) {
		chir_reserva_error( 'La página ha caducado. Recárgala e inténtalo de nuevo.', 403 );
	}

	// 2) Honeypot: campo oculto que una persona no rellena y un bot sí.
	if ( ! empty( $_POST['web'] ) ) {
		wp_send_json_success( array( 'mensaje' => 'Reserva recibida.' ) ); // Respuesta falsa para no dar pistas al bot.
	}

	// 3) Límite por IP: 5 envíos por hora.
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$clave = 'chir_rl_' . md5( $ip );
	$veces = (int) get_transient( $clave );
	if ( $veces >= 5 ) {
		chir_reserva_error( 'Demasiados intentos. Llámanos por teléfono o prueba más tarde.', 429 );
	}

	// 4) Validación de cada campo. Nunca se confía en lo que manda el navegador.
	$nombre   = isset( $_POST['nombre'] ) ? sanitize_text_field( wp_unslash( $_POST['nombre'] ) ) : '';
	$telefono = isset( $_POST['telefono'] ) ? preg_replace( '/[^0-9+]/', '', wp_unslash( $_POST['telefono'] ) ) : '';
	$personas = isset( $_POST['personas'] ) ? (int) $_POST['personas'] : 0;
	$fecha    = isset( $_POST['fecha'] ) ? sanitize_text_field( wp_unslash( $_POST['fecha'] ) ) : '';
	$hora     = isset( $_POST['hora'] ) ? sanitize_text_field( wp_unslash( $_POST['hora'] ) ) : '';
	$zona     = isset( $_POST['zona'] ) ? sanitize_text_field( wp_unslash( $_POST['zona'] ) ) : '';

	$len = function_exists( 'mb_strlen' ) ? mb_strlen( $nombre ) : strlen( $nombre );
	if ( $len < 2 || $len > 80 ) {
		chir_reserva_error( 'Escribe tu nombre (entre 2 y 80 caracteres).' );
	}
	if ( ! preg_match( '/^\+?[0-9]{9,15}$/', $telefono ) ) {
		chir_reserva_error( 'El teléfono no es válido. Usa entre 9 y 15 dígitos.' );
	}
	if ( $personas < 1 || $personas > 20 ) {
		chir_reserva_error( 'El número de personas debe estar entre 1 y 20.' );
	}

	$dt = DateTime::createFromFormat( '!Y-m-d', $fecha, wp_timezone() );
	if ( ! $dt || $dt->format( 'Y-m-d' ) !== $fecha ) {
		chir_reserva_error( 'La fecha no es válida.' );
	}
	$hoy = new DateTime( 'today', wp_timezone() );
	if ( $dt->format( 'Y-m-d' ) < $hoy->format( 'Y-m-d' ) ) {
		chir_reserva_error( 'No se puede reservar en una fecha pasada.' );
	}
	if ( $dt > ( clone $hoy )->modify( '+6 months' ) ) {
		chir_reserva_error( 'Solo aceptamos reservas con un máximo de 6 meses de antelación.' );
	}

	$d   = chir_datos();
	$dia = (int) $dt->format( 'w' );
	if ( ! empty( $d['horario'][ $dia ]['cerrado'] ) ) {
		chir_reserva_error( 'Ese día estamos cerrados. Elige otra fecha.' );
	}
	if ( ! in_array( $hora, chir_franjas_validas(), true ) ) {
		chir_reserva_error( 'La hora elegida no es válida.' );
	}
	if ( ! in_array( $zona, CHIR_ZONAS, true ) ) {
		chir_reserva_error( 'La zona elegida no es válida.' );
	}

	// 5) INSERT con consulta preparada: $wpdb->insert escapa cada valor según su formato (%s, %d).
	global $wpdb;
	$ok = $wpdb->insert(
		chir_tabla(),
		array(
			'nombre'   => $nombre,
			'telefono' => $telefono,
			'personas' => $personas,
			'fecha'    => $fecha,
			'hora'     => $hora . ':00',
			'zona'     => $zona,
			'estado'   => 'pendiente',
			'creada'   => current_time( 'mysql' ),
		),
		array( '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
	);

	if ( false === $ok ) {
		chir_reserva_error( 'No hemos podido guardar la reserva. Llámanos por teléfono.', 500 );
	}

	set_transient( $clave, $veces + 1, HOUR_IN_SECONDS );

	wp_send_json_success(
		array(
			'mensaje' => sprintf(
				'Reserva recibida, %s. Te esperamos el %s a las %s (%d %s). El local la confirmará llamando al %s.',
				$nombre,
				$dt->format( 'd/m/Y' ),
				$hora,
				$personas,
				1 === $personas ? 'persona' : 'personas',
				$telefono
			),
		)
	);
}
add_action( 'wp_ajax_nopriv_chir_reserva', 'chir_reserva_recibir' ); // Visitante sin sesión.
add_action( 'wp_ajax_chir_reserva', 'chir_reserva_recibir' );        // Usuario con sesión iniciada.

/* ------------------------------------------------------------------ */
/* 3. Panel de administración                                           */
/* ------------------------------------------------------------------ */

function chir_reservas_pendientes() {
	global $wpdb;
	$tabla = chir_tabla();
	return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tabla} WHERE estado = 'pendiente'" ); // phpcs:ignore WordPress.DB.PreparedSQL
}

add_action( 'admin_menu', function () {
	$n     = chir_reservas_pendientes();
	$burbu = $n > 0 ? ' <span class="awaiting-mod">' . $n . '</span>' : '';
	add_menu_page( 'Reservas', 'Reservas' . $burbu, 'manage_options', 'chir-reservas', 'chir_reservas_pagina', 'dashicons-calendar-alt', 26 );
} );

/** Cambiar estado o eliminar: se procesa en admin-post.php y se redirige de vuelta al panel. */
function chir_reserva_accion() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'No tienes permiso para hacer esto.', '', array( 'response' => 403 ) );
	}
	$id     = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
	$accion = isset( $_GET['accion'] ) ? sanitize_key( wp_unslash( $_GET['accion'] ) ) : '';
	check_admin_referer( 'chir_reserva_' . $accion . '_' . $id );

	global $wpdb;
	$aviso = '';
	if ( $id > 0 && in_array( $accion, array( 'confirmada', 'cancelada', 'pendiente' ), true ) ) {
		$wpdb->update( chir_tabla(), array( 'estado' => $accion ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
		$aviso = 'estado';
	} elseif ( $id > 0 && 'eliminar' === $accion ) {
		$wpdb->delete( chir_tabla(), array( 'id' => $id ), array( '%d' ) );
		$aviso = 'eliminada';
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'chir-reservas', 'aviso' => $aviso ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_chir_reserva_accion', 'chir_reserva_accion' );

function chir_reserva_enlace( $id, $accion, $texto, $clase = '', $confirmar = '' ) {
	$url = wp_nonce_url(
		add_query_arg( array( 'action' => 'chir_reserva_accion', 'id' => $id, 'accion' => $accion ), admin_url( 'admin-post.php' ) ),
		'chir_reserva_' . $accion . '_' . $id
	);
	$on = $confirmar ? ' onclick="return confirm(\'' . esc_js( $confirmar ) . '\');"' : '';
	return '<a class="button button-small ' . esc_attr( $clase ) . '" href="' . esc_url( $url ) . '"' . $on . '>' . esc_html( $texto ) . '</a> ';
}

function chir_reservas_pagina() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	global $wpdb;
	$tabla = chir_tabla();

	// Filtros (GET). Se validan antes de usarlos en la consulta.
	$f_fecha  = isset( $_GET['fecha'] ) ? sanitize_text_field( wp_unslash( $_GET['fecha'] ) ) : '';
	$f_estado = isset( $_GET['estado'] ) ? sanitize_key( wp_unslash( $_GET['estado'] ) ) : '';
	if ( $f_fecha && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $f_fecha ) ) {
		$f_fecha = '';
	}
	if ( $f_estado && ! in_array( $f_estado, CHIR_ESTADOS, true ) ) {
		$f_estado = '';
	}

	$donde = array();
	$args  = array();
	if ( $f_fecha ) {
		$donde[] = 'fecha = %s';
		$args[]  = $f_fecha;
	}
	if ( $f_estado ) {
		$donde[] = 'estado = %s';
		$args[]  = $f_estado;
	}
	$sql = "SELECT * FROM {$tabla}" . ( $donde ? ' WHERE ' . implode( ' AND ', $donde ) : '' ) . ' ORDER BY fecha ASC, hora ASC, id ASC LIMIT 500';
	$filas = $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ) ) : $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL

	$hoy     = current_time( 'Y-m-d' );
	$cuentas = $wpdb->get_results( "SELECT estado, COUNT(*) AS n FROM {$tabla} GROUP BY estado", OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL
	$n       = function ( $e ) use ( $cuentas ) {
		return isset( $cuentas[ $e ] ) ? (int) $cuentas[ $e ]->n : 0;
	};
	$hoy_n = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tabla} WHERE fecha = %s AND estado <> 'cancelada'", $hoy ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	$aviso = isset( $_GET['aviso'] ) ? sanitize_key( wp_unslash( $_GET['aviso'] ) ) : '';
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">Reservas</h1>
		<hr class="wp-header-end">

		<?php if ( 'estado' === $aviso ) : ?>
			<div class="notice notice-success is-dismissible"><p>Estado actualizado.</p></div>
		<?php elseif ( 'eliminada' === $aviso ) : ?>
			<div class="notice notice-success is-dismissible"><p>Reserva eliminada.</p></div>
		<?php endif; ?>

		<ul class="subsubsub">
			<li>Hoy: <strong><?php echo esc_html( $hoy_n ); ?></strong> |</li>
			<li>Pendientes: <strong><?php echo esc_html( $n( 'pendiente' ) ); ?></strong> |</li>
			<li>Confirmadas: <strong><?php echo esc_html( $n( 'confirmada' ) ); ?></strong> |</li>
			<li>Canceladas: <strong><?php echo esc_html( $n( 'cancelada' ) ); ?></strong></li>
		</ul>

		<form method="get" style="margin:1.2em 0 .6em;clear:both;">
			<input type="hidden" name="page" value="chir-reservas">
			<label for="f-fecha">Fecha</label>
			<input type="date" id="f-fecha" name="fecha" value="<?php echo esc_attr( $f_fecha ); ?>">
			<label for="f-estado">Estado</label>
			<select id="f-estado" name="estado">
				<option value="">Todos</option>
				<?php foreach ( CHIR_ESTADOS as $e ) : ?>
					<option value="<?php echo esc_attr( $e ); ?>" <?php selected( $f_estado, $e ); ?>><?php echo esc_html( ucfirst( $e ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<button class="button">Filtrar</button>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=chir-reservas&fecha=' . $hoy ) ); ?>">Solo hoy</a>
			<?php if ( $f_fecha || $f_estado ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=chir-reservas' ) ); ?>">Quitar filtros</a>
			<?php endif; ?>
		</form>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width:50px">#</th>
					<th>Fecha</th>
					<th style="width:60px">Hora</th>
					<th>Nombre</th>
					<th>Teléfono</th>
					<th style="width:70px">Pers.</th>
					<th>Zona</th>
					<th style="width:100px">Estado</th>
					<th style="width:260px">Acciones</th>
				</tr>
			</thead>
			<tbody>
			<?php if ( ! $filas ) : ?>
				<tr><td colspan="9">No hay reservas con estos filtros.</td></tr>
			<?php endif; ?>
			<?php foreach ( $filas as $r ) :
				$color = array( 'pendiente' => '#b45309', 'confirmada' => '#15803d', 'cancelada' => '#b91c1c' )[ $r->estado ] ?? '#444';
				?>
				<tr>
					<td><?php echo esc_html( $r->id ); ?></td>
					<td><?php echo esc_html( mysql2date( 'D d/m/Y', $r->fecha ) ); ?><?php echo $r->fecha === $hoy ? ' <strong>(hoy)</strong>' : ''; ?></td>
					<td><?php echo esc_html( substr( $r->hora, 0, 5 ) ); ?></td>
					<td><?php echo esc_html( $r->nombre ); ?></td>
					<td><a href="tel:<?php echo esc_attr( $r->telefono ); ?>"><?php echo esc_html( $r->telefono ); ?></a></td>
					<td><?php echo esc_html( $r->personas ); ?></td>
					<td><?php echo esc_html( $r->zona ); ?></td>
					<td><strong style="color:<?php echo esc_attr( $color ); ?>"><?php echo esc_html( ucfirst( $r->estado ) ); ?></strong></td>
					<td>
						<?php
						if ( 'confirmada' !== $r->estado ) {
							echo chir_reserva_enlace( $r->id, 'confirmada', 'Confirmar', 'button-primary' ); // phpcs:ignore WordPress.Security.EscapeOutput
						}
						if ( 'cancelada' !== $r->estado ) {
							echo chir_reserva_enlace( $r->id, 'cancelada', 'Cancelar' ); // phpcs:ignore WordPress.Security.EscapeOutput
						}
						echo chir_reserva_enlace( $r->id, 'eliminar', 'Eliminar', 'button-link-delete', '¿Eliminar esta reserva para siempre?' ); // phpcs:ignore WordPress.Security.EscapeOutput
						?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}
