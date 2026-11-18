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

	/* 4. Formulario de reserva → se envía por AJAX a WordPress y se guarda en MySQL. */
	var form = document.getElementById('form-reserva');
	if (form && window.CHIR) {
		var fecha = document.getElementById('r-fecha');
		var estado = document.getElementById('reserva-estado');
		var boton = form.querySelector('button[type="submit"]');
		var hoyISO = new Date();
		hoyISO = hoyISO.getFullYear() + '-' + String(hoyISO.getMonth() + 1).padStart(2, '0') + '-' + String(hoyISO.getDate()).padStart(2, '0');
		fecha.min = hoyISO;
		fecha.value = hoyISO;

		function mostrar(texto, ok) {
			estado.textContent = texto;
			estado.className = 'form__estado ' + (ok ? 'form__estado--ok' : 'form__estado--error');
			estado.hidden = false;
		}

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var datos = new FormData(form);
			datos.append('action', 'chir_reserva');
			datos.append('nonce', window.CHIR.nonce);

			boton.disabled = true;
			boton.textContent = 'Enviando…';
			estado.hidden = true;

			fetch(window.CHIR.ajax, { method: 'POST', body: datos, credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (r) {
					if (r && r.success) {
						mostrar(r.data.mensaje, true);
						form.reset();
						fecha.value = hoyISO;
					} else {
						mostrar((r && r.data && r.data.mensaje) || 'No se ha podido enviar la reserva.', false);
					}
				})
				.catch(function () {
					mostrar('No hay conexión con el servidor. Inténtalo de nuevo o llámanos.', false);
				})
				.finally(function () {
					boton.disabled = false;
					boton.textContent = 'Reservar mesa';
				});
		});
	}
})();
