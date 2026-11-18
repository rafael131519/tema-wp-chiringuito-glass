(function () {
	'use strict';

	var raiz = document.documentElement;
	var sinMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	/* 1. Progreso de scroll → variable CSS --p (mueve el fondo cielo → mar → arena). */
	var pendiente = false;
	function actualizarScroll() {
		pendiente = false;
		var max = document.documentElement.scrollHeight - window.innerHeight;
		var p = max > 0 ? Math.min(1, Math.max(0, window.scrollY / max)) : 0;
		raiz.style.setProperty('--p', p.toFixed(4));
	}
	function pedirScroll() {
		if (!pendiente) {
			pendiente = true;
			requestAnimationFrame(actualizarScroll);
		}
	}
	window.addEventListener('scroll', pedirScroll, { passive: true });
	window.addEventListener('resize', pedirScroll);
	actualizarScroll();

	/* 2. Aparición de paneles al entrar en pantalla. */
	var items = document.querySelectorAll('.rv');
	if ('IntersectionObserver' in window && !sinMovimiento) {
		var obs = new IntersectionObserver(function (entradas) {
			entradas.forEach(function (e) {
				if (e.isIntersecting) {
					e.target.classList.add('is-in');
					obs.unobserve(e.target);
				}
			});
		}, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
		items.forEach(function (el, i) {
			// Pequeño escalonado solo entre hermanos de una misma rejilla.
			el.style.transitionDelay = (i % 2) * 90 + 'ms';
			obs.observe(el);
		});
	} else {
		items.forEach(function (el) { el.classList.add('is-in'); });
	}

	/* 3. Estado "abierto ahora" según el horario de datos.php (hora de Madrid). */
	var hoy = document.querySelector('.hoy');
	if (hoy) {
		var horario = JSON.parse(hoy.getAttribute('data-horario') || '{}');
		var dias = JSON.parse(hoy.getAttribute('data-dias') || '{}');
		var texto = document.getElementById('estado-texto');

		var partes = new Intl.DateTimeFormat('en-GB', {
			timeZone: 'Europe/Madrid', weekday: 'short', hour: '2-digit', minute: '2-digit', hour12: false
		}).formatToParts(new Date());
		var get = function (t) {
			for (var i = 0; i < partes.length; i++) { if (partes[i].type === t) { return partes[i].value; } }
			return '';
		};
		var mapa = { Sun: 0, Mon: 1, Tue: 2, Wed: 3, Thu: 4, Fri: 5, Sat: 6 };
		var dia = mapa[get('weekday')];
		var ahora = (parseInt(get('hour'), 10) % 24) * 60 + parseInt(get('minute'), 10);

		var fmt = function (min) {
			var h = Math.floor(min / 60) % 24, m = min % 60;
			return (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m;
		};

		var hoyH = horario[dia];
		var abierto = hoyH && ahora >= hoyH[0] && ahora < hoyH[1];

		if (abierto) {
			hoy.classList.add('abierto');
			texto.textContent = 'Abierto ahora, hasta las ' + fmt(hoyH[1]);
		} else if (hoyH && ahora < hoyH[0]) {
			texto.textContent = 'Cerrado. Abre hoy a las ' + fmt(hoyH[0]);
		} else {
			var etiqueta = null;
			for (var k = 1; k <= 7; k++) {
				var d2 = (dia + k) % 7;
				if (horario[d2]) {
					etiqueta = (k === 1 ? 'mañana' : 'el ' + String(dias[d2]).toLowerCase()) + ' a las ' + fmt(horario[d2][0]);
					break;
				}
			}
			texto.textContent = etiqueta ? 'Cerrado. Abre ' + etiqueta : 'Cerrado';
		}

		var fila = document.querySelector('.tabla-horario tr[data-dia="' + dia + '"]');
		if (fila) { fila.classList.add('hoy-fila'); }
	}

	/* 4. Formulario de reserva → mensaje de WhatsApp. */
	var form = document.getElementById('form-reserva');
	if (form) {
		var fecha = document.getElementById('r-fecha');
		var hoyISO = new Date();
		hoyISO = hoyISO.getFullYear() + '-' + String(hoyISO.getMonth() + 1).padStart(2, '0') + '-' + String(hoyISO.getDate()).padStart(2, '0');
		fecha.min = hoyISO;
		fecha.value = hoyISO;

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var f = new FormData(form);
			var partesFecha = String(f.get('fecha')).split('-');
			var fechaTxt = partesFecha.length === 3 ? partesFecha[2] + '/' + partesFecha[1] + '/' + partesFecha[0] : f.get('fecha');
			var n = parseInt(f.get('personas'), 10);
			var msg = 'Hola, soy ' + f.get('nombre').toString().trim() + '. Quiero reservar mesa en ' + form.getAttribute('data-nombre') +
				' para ' + n + (n === 1 ? ' persona' : ' personas') +
				' el ' + fechaTxt + ' a las ' + f.get('hora') +
				'. Zona: ' + f.get('zona').toString().toLowerCase() + '.';
			window.open(form.getAttribute('data-wa') + '?text=' + encodeURIComponent(msg), '_blank', 'noopener');
		});
	}
})();
