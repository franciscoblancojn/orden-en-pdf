# 📋 Registro de cambios

Todos los cambios importantes del plugin Orden en PDF. Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/).

> ✍️ Antes de publicar, agrega arriba una sección `## [Sin publicar]` con los cambios. `npm run push-v -- patch|minor|major` la renombra a `## [X.Y.Z] - fecha`, y no publica si esa sección no existe.

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
