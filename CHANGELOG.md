# 📋 Registro de cambios

Todos los cambios importantes del plugin Orden en PDF. Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/).

> ✍️ Antes de publicar, agrega arriba una sección `## [Sin publicar]` con los cambios. `npm run push-v -- patch|minor|major` la renombra a `## [X.Y.Z] - fecha`, y no publica si esa sección no existe.

## [1.0.5] - 2026-11-07

### 🐛 Corregido
- 🪪 La **cédula** no salía en el PDF cuando la guardó un plugin que todavía usa `update_post_meta()` (por ejemplo, el campo «Cédula» de algunos plugins de envío). Con las tablas de pedidos de WooCommerce (HPOS) esos datos no están en los metadatos del pedido sino en los de su entrada: ahora, si el pedido no tiene el campo, se busca también ahí. Nuevo filtro `oep_order_meta` para resolver campos guardados en otro lugar.

## [1.0.4] - 2026-11-07

### ✨ Añadido
- 🪪 **Cédula del cliente en la caja de Facturación** («Cédula: 1234567890»). Se busca en los campos del pedido configurables en la pestaña PDF → «Documento del cliente»; por defecto `_billing_id` / `billing_id` (campo Cédula de facturación) y `_cedula` / `cedula` (campo Cédula que agregan algunos plugins de envío). Si el cliente la escribió en los dos campos con números distintos, salen los dos («111111 / 222222»); si es el mismo número, una sola vez. La etiqueta se puede cambiar (p. ej. «Cédula / NIT») o dejar vacía.

## [1.0.3] - 2026-11-07

### 🔄 Cambiado
- 🗺️ En **Facturación** y **Envío** el departamento sale con su nombre y no con el código (ANT → Antioquia), usando la lista de estados de WooCommerce (incluye los que agregan otros plugins). Si el código no está en la lista, se muestra tal cual. La ciudad y el departamento van separados por coma: «Medellín, Antioquia 050001».

## [1.0.2] - 2026-11-06

### ✨ Añadido
- 🖼️ **Miniatura en la tabla de productos**: cada línea muestra la imagen del producto o, si es una variación, la suya propia (o la del padre si la variación no tiene). Se usa la miniatura de WooCommerce en un tamaño reducido y se incrusta como data URI, así que el PDF no depende de internet.

### 🔄 Cambiado
- 🧾 **Facturación y envío**: las dos cajas ya no llevan fondo gris ni borde izquierdo; el contenido queda alineado arriba y a la izquierda.

## [1.0.1] - 2026-11-06

### 🐛 Corregido
- 😀 **Emojis en el PDF**: la cabecera, la nota del cliente, las notas del pedido y el pie ahora muestran los emojis reales (`🏭`, `📩`, `☎️`, `🏠`, `🏘️`, `🏙️`, `🌎`, `🏢`…) en lugar de glifos rotos o cuadros. Se incluye `assets/fonts/OEPEmoji.ttf`, una versión de Noto Emoji remapeada al área de uso privado porque DOMPDF no maneja caracteres fuera del plano básico, y el servicio `OEP_EMOJI` los convierte y dibuja. Si la fuente no se puede registrar, los emojis se omiten sin romper el documento.
- 🌍 **Pie de página con emojis**: el texto del pie se dibuja por segmentos (texto y emoji) y se recorta sin partir un emoji.

## [1.0.0] - 2026-11-06

### ✨ Añadido
- 📄 **Descarga del pedido en PDF** desde el detalle del pedido en el admin, con un botón en la caja «Orden en PDF» (compatible con el almacenamiento clásico y con HPOS).
- 📧 **Adjunto en los correos del administrador**: nuevo pedido (activo por defecto), cancelado y fallido, cada uno configurable por separado. Los correos al cliente nunca llevan el adjunto.
- 🖼️ **Logo del PDF**: usa el logo del tema (Personalizar → Identidad del sitio) y permite subir otro distinto solo para el PDF, con ancho configurable.
- 🧾 **Todos los datos del pedido**: número, fecha, estado, método de pago, transacción, método de envío, cliente, direcciones de facturación y envío, productos con SKU y variaciones, totales de WooCommerce, nota del cliente e historial de notas opcional.
- ⚙️ **Ajustes**: nombre de la empresa, datos de cabecera, título del documento, pie de página, tamaño de papel (A4/Carta), color de acento y vista previa del último pedido.
- 📝 **Log** de descargas, adjuntos, errores y cambios de ajustes (`FWUSystemLog`).
- 🔄 **Actualizaciones desde GitHub** con `FWUUpdate` de `wordpress_utils`, con el mismo sistema que los demás plugins.
