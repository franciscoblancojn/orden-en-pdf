# Orden en PDF — Reglas para IAs

## Código
- **PHP 7.4+**, estándares de WordPress. Clases y funciones con prefijo `OEP_`.
- **JavaScript ES5 + jQuery** (sin arrow functions, `let`/`const` ni template literals). Funciones con prefijo `oep_`.
- **CSS** con prefijo `oep-` (las clases `fwue-` son de `wordpress_utils`).
- Escapar toda salida (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`) y sanitizar toda entrada.

## Seguridad
- Descarga: `check_admin_referer('oep_download_' . $order_id)` + `current_user_can('edit_shop_orders')` (ver `OEP_API_DOWNLOAD::download()`).
- Guardar ajustes: `check_admin_referer(OEP_API_SETTINGS::ACTION)` + `current_user_can('manage_options')`.
- Los PDFs temporales de los correos van en `uploads/orden-en-pdf-tmp/`, con `index.php` y `.htaccess` de bloqueo, y se borran al terminar la petición (`shutdown`). No cambiar a una carpeta pública sin protección.
- El token de GitHub de `FWUUpdate` está en `index.php` (`token_array_split`), como en los demás plugins del autor. No agregar otras claves o secretos al código: `npm run check` falla si encuentra un token escrito de corrido.

## Arquitectura
- `index.php`: cabecera y constantes `OEP_*`. Sin lógica. WooCommerce es **obligatorio**.
- `src/_.php`: cargador maestro. Cada carpeta tiene su `_.php`.
- Plugin genérico: **no** usar nombres ni textos de un sitio concreto (el contenido por defecto es solo un ejemplo editable).
- `OEP_ORDER` es la única fuente de los datos del pedido: devuelve texto plano y la plantilla solo escapa. Si se agrega un dato, agregarlo ahí y en `src/templates/pdf.php`.
- `OEP_PDF` es la única fuente del documento: arma el HTML, configura DOMPDF (sin recursos remotos), agrega el pie con el número de página y genera la descarga/adjunto. El logo va como data URI (`OEP_LOGO`).
- **Emojis:** DOMPDF (Cpdf) solo dibuja caracteres del plano básico de Unicode. Por eso `assets/fonts/OEPEmoji.ttf` es Noto Emoji con los emojis remapeados al área de uso privado (`U+E000+`) y `src/data/emoji-map.php` guarda la correspondencia. `OEP_EMOJI::wrap()` convierte y envuelve cada emoji en `<span class="oep-emoji">`; el pie usa `OEP_EMOJI::segments()`. `OEP_EMOJI::register()` se llama en `OEP_PDF::build()` **antes** de `html()`. Si la fuente no carga, los emojis se eliminan: no imprimir glifos rotos. Para regenerar fuente y mapa: `python3 scripts/build-emoji-font.py` (requiere `fonttools`). No editar `OEPEmoji.ttf` ni `emoji-map.php` a mano.
- `OEP_HOOK_EMAIL` usa `woocommerce_email_attachments`: descarta siempre los correos al cliente (`is_customer_email()`) y respeta los tipos activados en `OEP_USE_DATA_CONFIG::attachs_to()`.
- Compatibilidad HPOS declarada en `OEP_HOOK_ORDER::declare_compatibility()`.
- Toda operación relevante (descarga, adjunto, error, ajustes) queda en el log mediante `OEP_LOG_SERVICE::add('tipo', [...])`, que no falla si `wordpress_utils` no declara `FWUSystemLog` en el frontend.

## Publicar
- Anotar los cambios en `CHANGELOG.md` bajo `## [Sin publicar]`.
- `npm run push-v -- patch|minor|major`.
