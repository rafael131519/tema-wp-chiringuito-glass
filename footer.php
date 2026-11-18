<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$d = chir_datos();
?>
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
