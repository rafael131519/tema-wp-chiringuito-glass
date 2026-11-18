<?php
/**
 * DATOS DEL NEGOCIO — es el único archivo que hay que editar para otra marca.
 * Todos los datos son de demostración: cambiar teléfono, WhatsApp, email y dirección.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'nombre'      => 'Chiringuito Salitre',
	'eslogan'     => 'Arroz, pescado y horchata a pie de arena',
	'descripcion' => 'Cocina de arroces en paella, pescado de lonja y bebidas frías en la Malvarrosa. Mesas en terraza y sobre la arena, con vistas al mar.',

	'direccion'   => 'Passeig Marítim de la Malvarrosa, s/n, 46011 València',
	'mapa_url'    => 'https://www.google.com/maps/search/?api=1&query=Playa+de+la+Malvarrosa+Valencia',
	'telefono'    => '600 000 000',
	'whatsapp'    => '34600000000', // Con el 34 delante y sin espacios.
	'email'       => 'hola@chiringuitosalitre.es',
	'instagram'   => 'chiringuitosalitre',

	// Si el negocio usa TheFork, CoverManager u otra web de reservas, pegar aquí el enlace.
	// Vacío = el formulario de la web abre WhatsApp con el mensaje ya escrito.
	'reserva_url' => '',

	// Domingo = 0 ... sábado = 6. 'cierra' => '24:00' significa medianoche. 'cerrado' => true para día libre.
	'horario'     => array(
		1 => array( 'dia' => 'Lunes',     'cerrado' => true ),
		2 => array( 'dia' => 'Martes',    'abre' => '12:00', 'cierra' => '23:00' ),
		3 => array( 'dia' => 'Miércoles', 'abre' => '12:00', 'cierra' => '23:00' ),
		4 => array( 'dia' => 'Jueves',    'abre' => '12:00', 'cierra' => '23:00' ),
		5 => array( 'dia' => 'Viernes',   'abre' => '12:00', 'cierra' => '24:00' ),
		6 => array( 'dia' => 'Sábado',    'abre' => '11:00', 'cierra' => '24:00' ),
		0 => array( 'dia' => 'Domingo',   'abre' => '11:00', 'cierra' => '23:00' ),
	),

	// Franjas del selector de hora del formulario de reserva.
	'reserva_desde' => '13:00',
	'reserva_hasta' => '22:30',

	'carta_nota'  => 'Precios con IVA. Los arroces se hacen por encargo y tardan 25 minutos.',
	'carta'       => array(
		'Arroces' => array(
			array( 'Paella valenciana', '19 €', 'Por persona, mínimo 2' ),
			array( 'Arroz del señoret', '21 €', 'Por persona, mínimo 2' ),
			array( 'Fideuà de marisco', '20 €', 'Por persona, mínimo 2' ),
			array( 'Arroz meloso de verduras', '16 €', 'Por persona, mínimo 2' ),
		),
		'Para picar' => array(
			array( 'Esgarraet', '9 €', 'Pimiento asado y bacalao' ),
			array( 'Clóchinas', '12 €', 'Mejillón valenciano, de temporada' ),
			array( 'Calamares a la andaluza', '13 €', '' ),
			array( 'Ensaladilla de la casa', '8 €', '' ),
		),
		'Del mar' => array(
			array( 'Dorada a la espalda', '17 €', '' ),
			array( 'Sepia a la plancha', '15 €', '' ),
			array( 'Pescado del día', 'Según lonja', 'Se enseña en mesa antes de cocinarlo' ),
		),
		'Bebidas' => array(
			array( 'Horchata con fartons', '4,50 €', '' ),
			array( 'Agua de Valencia', '7 €', 'Jarra para compartir' ),
			array( 'Tinto de verano', '4 €', '' ),
			array( 'Caña', '2,50 €', '' ),
		),
	),

	// Galería: pie de foto de cada imagen. Fotos reales en assets/img/galeria-1.jpg ... galeria-6.jpg
	'galeria'     => array(
		'Paella al fuego',
		'Terraza al atardecer',
		'Mesas sobre la arena',
		'Horchata y fartons',
		'Barra de bebidas',
		'Pescado del día',
	),
);
