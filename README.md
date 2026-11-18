# Chiringuito Glass — Tema de WordPress con reservas en MySQL

Tema **one-page de WordPress** para chiringuitos y restaurantes de playa, desarrollado a medida en **PHP, MySQL, HTML5, CSS3 y JavaScript**. Versión **1.1.0**: las reservas se guardan en base de datos y se gestionan desde un panel propio en el administrador.

## Capturas

| | |
|---|---|
| ![](capturas/captura-1.png) | ![](capturas/captura-2.png) |
| ![](capturas/captura-3.png) | ![](capturas/captura-4.png) |

**Panel «Reservas» en el administrador de WordPress** (estados: confirmada, pendiente, cancelada):

![](capturas/05-admin-reservas.png)

## Características
- Estilo **glassmorphism** (paneles translúcidos con desenfoque) y **olas animadas**.
- Secciones de inicio, carta, terraza y reservas en una sola página; datos editables en `inc/datos.php`.
- Indicador de estado abierto/cerrado y diseño **responsive**.

## Sistema de reservas (v1.1.0)
- **Tabla MySQL** `wp_reservas` (nombre, teléfono, personas, fecha, hora, zona, estado, fecha de alta), creada con `dbDelta()` al activar el tema.
- **Formulario por AJAX** (`fetch` → `admin-ajax.php`): guarda la reserva sin recargar la página y muestra confirmación o error.
- **Panel «Reservas»** en el admin (`add_menu_page`): listado ordenado por fecha, contadores (hoy, pendientes, confirmadas, canceladas), filtro por fecha y estado, y acciones confirmar / cancelar / eliminar. Solo accesible con permiso `manage_options`.

### Flujo
```
formulario (main.js) → admin-ajax.php?action=chir_reserva → chir_reserva_recibir() → INSERT en wp_reservas
admin → menú «Reservas» → chir_reservas_pagina() → SELECT con filtros → acciones vía admin-post.php
```

### Seguridad
- **Nonce** en el formulario y en cada acción del panel (`check_ajax_referer`, `check_admin_referer`).
- **Consultas preparadas** con `$wpdb->insert / update / delete / prepare`.
- **Validación en servidor** de nombre, teléfono, personas, fecha (no pasada, máximo 6 meses, día abierto), hora y zona.
- **Honeypot** anti-bots y **límite de 5 envíos por hora y por IP** (transients).
- Salida escapada con `esc_html`, `esc_attr` y `esc_url`.

## Estructura
```
functions.php        carga de estilos, scripts y inc/
index.php            plantilla de la página
inc/datos.php        datos del negocio (único archivo a editar para otra marca)
inc/reservas.php     tabla, recepción AJAX y panel de administración
assets/              CSS y JavaScript
```

## Instalación
Copiar en `wp-content/themes/`, activar y editar `inc/datos.php`. WordPress 6.0+, PHP 7.4+, MySQL/MariaDB.

---
Rafael Izquierdo · KizaryDev
