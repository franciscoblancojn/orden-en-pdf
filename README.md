# 📄 Orden en PDF

> Plugin de WordPress para WooCommerce: **descarga el pedido en PDF** desde el detalle del pedido en el admin y **adjunta ese PDF a los correos que recibe el administrador**. El documento carga el logo del sitio, los datos del cliente, los productos, los totales y las notas.

**Versión:** 1.0.3 · **WordPress:** 5.8+ · **PHP:** 7.4+ · **WooCommerce:** requerido · **Licencia:** GPLv2+

📋 [Registro de cambios](CHANGELOG.md) · 🤖 [Reglas para IAs](AGENTS.md) · 📦 [Releases](https://github.com/franciscoblancojn/orden-en-pdf/releases)

---

## 💡 Cómo funciona

| Dónde | Qué hace |
|---|---|
| **Detalle del pedido** (admin) | Caja **«Orden en PDF»** en la barra lateral con el botón **Descargar PDF**. Funciona con el almacenamiento clásico de pedidos y con HPOS. |
| **Correo «Nuevo pedido»** (admin) | Adjunta el PDF. Activo por defecto. |
| **Correo «Pedido cancelado»** (admin) | Adjunta el PDF. Desactivado por defecto. |
| **Correo «Pedido fallido»** (admin) | Adjunta el PDF. Desactivado por defecto. |
| **Correos al cliente** | **Nunca** llevan el adjunto. |

El PDF se genera en el momento: no se guardan copias de los pedidos en el servidor. Para el correo se crea un archivo temporal en `uploads/orden-en-pdf-tmp/` (con `index.php` y `.htaccess` de bloqueo) que se borra al terminar el envío.

### Contenido del PDF

- Logo del sitio (o el logo elegido en los ajustes) y datos de cabecera.
- Número, fecha, estado, método de pago, transacción y método de envío.
- Cliente y direcciones de facturación y envío (email y teléfono en facturación).
- Productos: miniatura del producto o de la variación, nombre, SKU, variaciones, cantidad, precio unitario y total.
- Totales de WooCommerce: subtotal, descuento, envío, impuestos, total y devoluciones.
- Nota del cliente (opcional) e historial de notas del pedido (opcional).
- Pie de página configurable y numeración «Página X de Y».
- **Emojis** en la cabecera, la nota del cliente, las notas del pedido y el pie (por ejemplo `🏭`, `📩`, `☎️`). Se dibujan con la fuente incluida `assets/fonts/OEPEmoji.ttf`. DOMPDF solo maneja caracteres del plano básico de Unicode, así que la fuente es una versión de Noto Emoji con los emojis remapeados al área de uso privado (`U+E000` en adelante); la correspondencia está en `src/data/emoji-map.php` y se regenera con `scripts/build-emoji-font.py`. Si la fuente no se puede registrar (uploads sin permiso de escritura), los emojis se omiten para no imprimir glifos rotos.

## ⚙️ Configuración

**Admin → Orden en PDF**

### Pestaña PDF

- **Logo:** si no se elige uno, se usa el logo del tema (**Personalizar → Identidad del sitio**). Si tampoco hay, se muestra el nombre del sitio en texto. El logo se incrusta como data URI: DOMPDF no necesita acceder a archivos ni a internet.
- **Ancho del logo**, nombre de la empresa, datos de cabecera (NIT, dirección, teléfono…), título del documento, pie de página, tamaño de papel (A4 o Carta) y color de acento.
- **Contenido:** nota del cliente e historial de notas.
- **Vista previa:** abre el PDF del último pedido con los ajustes guardados.

### Pestaña Correos

Casillas independientes para **Nuevo pedido**, **Pedido cancelado** y **Pedido fallido**. Solo se adjunta a esos correos, que WooCommerce envía al administrador. Si un correo está desactivado en **WooCommerce → Ajustes → Correos**, no se envía y tampoco se genera el adjunto.

### Filtro

```php
// Desactivar el adjunto para un pedido concreto.
add_filter('oep_attach_pdf', function ($attach, $email_id, $order, $email) {
    if (1000 === $order->get_id()) {
        return false;
    }
    return $attach;
}, 10, 4);
```

## 📋 Requisitos

- WordPress 5.8+
- PHP 7.4+
- WooCommerce 5.0+ activo
- Permiso `manage_options` para los ajustes y `edit_shop_orders` para descargar pedidos
- Extensiones PHP `dom` y `mbstring` (las exige DOMPDF). `gd` es recomendable; sin ella el logo se convierte a JPEG con Imagick o con el editor de imágenes de WordPress.

## ⚙️ Instalación

1. Descarga el zip de la rama `master`: https://github.com/franciscoblancojn/orden-en-pdf/archive/refs/heads/master.zip
2. En WordPress: **Plugins → Añadir nuevo → Subir plugin** y actívalo.
3. Ve a **Orden en PDF**, revisa el logo y los datos de cabecera y guarda.
4. Activa los correos del administrador a los que quieras adjuntar el PDF.

## 🔄 Actualizaciones

- Usa `FWUUpdate` (librería `wordpress_utils`), configurado en `index.php` (repositorio, rama `master` y token), como en los demás plugins.
- En **Plugins** aparece «Actualizar» cuando hay una release más nueva en GitHub. Para eso, cada versión necesita su release: la crea el workflow al subir el tag.
- `FWUUpdate` instala el zip de la rama `master`, que se descomprime en la carpeta `orden-en-pdf-master`. Conviene hacer la **primera instalación también desde ese zip**, para que la carpeta no cambie con la primera actualización: si cambia, WordPress desactiva el plugin.

## 🧑‍💻 Desarrollo

```bash
npm run install          # composer install → libs/ (dompdf/dompdf y wordpress_utils)
npm run check            # validaciones: php -l, JS ES5, require_once, dependencias y secretos
npm run push-v -- patch  # o minor | major → publica una versión
```

`npm run push-v`:

1. exige una sección `## [Sin publicar]` en [`CHANGELOG.md`](CHANGELOG.md) y que `npm run check` pase;
2. sube la versión en `index.php`, `package.json` y este README;
3. cambia `## [Sin publicar]` por `## [X.Y.Z] - fecha`;
4. hace commit «Release X.Y.Z», crea el tag `X.Y.Z` y hace push.

Después, GitHub Actions crea la release con `orden-en-pdf.zip`.

## 🗂️ Estructura

```
orden-en-pdf/
├── index.php                 # Cabecera del plugin, constantes OEP_*, carga
├── libs/                     # dompdf/dompdf + franciscoblancojn/wordpress_utils (Composer → libs)
├── bin/harness.sh            # Validaciones (npm run check)
├── scripts/bump-version.sh   # npm run push-v
├── scripts/build-emoji-font.py # Regenera OEPEmoji.ttf y el mapa de emojis
├── assets/fonts/OEPEmoji.ttf # Noto Emoji remapeada al área de uso privado (+ OFL)
├── .github/workflows/        # Release con el zip al subir un tag
└── src/
    ├── _.php                 # Cargador maestro
    ├── data/                 # Config, log y emoji-map.php (Unicode → área privada)
    ├── services/             # OEP_ORDER (datos), OEP_LOGO / OEP_IMAGE (data URI), OEP_EMOJI (fuente), OEP_PDF (DOMPDF)
    ├── hooks/                # order (caja en el detalle, HPOS), email (adjunto al admin)
    ├── api/                  # descarga y guardado de ajustes
    ├── templates/            # Plantilla HTML del PDF
    ├── page/                 # Menú «Orden en PDF» y secciones (FWUPage)
    ├── js/admin.js           # Selector de logo, color picker (ES5 + jQuery)
    └── css/admin.css
```
